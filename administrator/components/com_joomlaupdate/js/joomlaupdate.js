/*
 * Joomla Update's scripts: AES (Chris Veness), the update run (Akeeba's CMS Update, GPL 3 or later), then the main page's
 * extraction settings and pre-update check (Joomla), which start only on the main page.
 */

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  AES implementation in JavaScript (c) Chris Veness 2005-2010                                   */
/*   - see http://csrc.nist.gov/publications/PubsFIPS.html#197                                    */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

var Aes = {};  // Aes namespace

/**
 * AES Cipher function: encrypt 'input' state with Rijndael algorithm
 *   applies Nr rounds (10/12/14) using key schedule w for 'add round key' stage
 *
 * @param {Number[]} input 16-byte (128-bit) input state array
 * @param {Number[][]} w   Key schedule as 2D byte-array (Nr+1 x Nb bytes)
 * @returns {Number[]}     Encrypted output state array
 */
Aes.Cipher = function(input, w) {    // main Cipher function [§5.1]
  var Nb = 4;               // block size (in words): no of columns in state (fixed at 4 for AES)
  var Nr = w.length/Nb - 1; // no of rounds: 10/12/14 for 128/192/256-bit keys

  var state = [[],[],[],[]];  // initialise 4xNb byte-array 'state' with input [§3.4]
  for (var i=0; i<4*Nb; i++) state[i%4][Math.floor(i/4)] = input[i];

  state = Aes.AddRoundKey(state, w, 0, Nb);

  for (var round=1; round<Nr; round++) {
    state = Aes.SubBytes(state, Nb);
    state = Aes.ShiftRows(state, Nb);
    state = Aes.MixColumns(state, Nb);
    state = Aes.AddRoundKey(state, w, round, Nb);
  }

  state = Aes.SubBytes(state, Nb);
  state = Aes.ShiftRows(state, Nb);
  state = Aes.AddRoundKey(state, w, Nr, Nb);

  var output = new Array(4*Nb);  // convert state to 1-d array before returning [§3.4]
  for (var i=0; i<4*Nb; i++) output[i] = state[i%4][Math.floor(i/4)];
  return output;
}

/**
 * Perform Key Expansion to generate a Key Schedule
 *
 * @param {Number[]} key Key as 16/24/32-byte array
 * @returns {Number[][]} Expanded key schedule as 2D byte-array (Nr+1 x Nb bytes)
 */
Aes.KeyExpansion = function(key) {  // generate Key Schedule (byte-array Nr+1 x Nb) from Key [§5.2]
  var Nb = 4;            // block size (in words): no of columns in state (fixed at 4 for AES)
  var Nk = key.length/4  // key length (in words): 4/6/8 for 128/192/256-bit keys
  var Nr = Nk + 6;       // no of rounds: 10/12/14 for 128/192/256-bit keys

  var w = new Array(Nb*(Nr+1));
  var temp = new Array(4);

  for (var i=0; i<Nk; i++) {
    var r = [key[4*i], key[4*i+1], key[4*i+2], key[4*i+3]];
    w[i] = r;
  }

  for (var i=Nk; i<(Nb*(Nr+1)); i++) {
    w[i] = new Array(4);
    for (var t=0; t<4; t++) temp[t] = w[i-1][t];
    if (i % Nk == 0) {
      temp = Aes.SubWord(Aes.RotWord(temp));
      for (var t=0; t<4; t++) temp[t] ^= Aes.Rcon[i/Nk][t];
    } else if (Nk > 6 && i%Nk == 4) {
      temp = Aes.SubWord(temp);
    }
    for (var t=0; t<4; t++) w[i][t] = w[i-Nk][t] ^ temp[t];
  }

  return w;
}

/*
 * ---- remaining routines are private, not called externally ----
 */
 
Aes.SubBytes = function(s, Nb) {    // apply SBox to state S [§5.1.1]
  for (var r=0; r<4; r++) {
    for (var c=0; c<Nb; c++) s[r][c] = Aes.Sbox[s[r][c]];
  }
  return s;
}

Aes.ShiftRows = function(s, Nb) {    // shift row r of state S left by r bytes [§5.1.2]
  var t = new Array(4);
  for (var r=1; r<4; r++) {
    for (var c=0; c<4; c++) t[c] = s[r][(c+r)%Nb];  // shift into temp copy
    for (var c=0; c<4; c++) s[r][c] = t[c];         // and copy back
  }          // note that this will work for Nb=4,5,6, but not 7,8 (always 4 for AES):
  return s;  // see asmaes.sourceforge.net/rijndael/rijndaelImplementation.pdf
}

Aes.MixColumns = function(s, Nb) {   // combine bytes of each col of state S [§5.1.3]
  for (var c=0; c<4; c++) {
    var a = new Array(4);  // 'a' is a copy of the current column from 's'
    var b = new Array(4);  // 'b' is a•{02} in GF(2^8)
    for (var i=0; i<4; i++) {
      a[i] = s[i][c];
      b[i] = s[i][c]&0x80 ? s[i][c]<<1 ^ 0x011b : s[i][c]<<1;
    }
    // a[n] ^ b[n] is a•{03} in GF(2^8)
    s[0][c] = b[0] ^ a[1] ^ b[1] ^ a[2] ^ a[3]; // 2*a0 + 3*a1 + a2 + a3
    s[1][c] = a[0] ^ b[1] ^ a[2] ^ b[2] ^ a[3]; // a0 * 2*a1 + 3*a2 + a3
    s[2][c] = a[0] ^ a[1] ^ b[2] ^ a[3] ^ b[3]; // a0 + a1 + 2*a2 + 3*a3
    s[3][c] = a[0] ^ b[0] ^ a[1] ^ a[2] ^ b[3]; // 3*a0 + a1 + a2 + 2*a3
  }
  return s;
}

Aes.AddRoundKey = function(state, w, rnd, Nb) {  // xor Round Key into state S [§5.1.4]
  for (var r=0; r<4; r++) {
    for (var c=0; c<Nb; c++) state[r][c] ^= w[rnd*4+c][r];
  }
  return state;
}

Aes.SubWord = function(w) {    // apply SBox to 4-byte word w
  for (var i=0; i<4; i++) w[i] = Aes.Sbox[w[i]];
  return w;
}

Aes.RotWord = function(w) {    // rotate 4-byte word w left by one byte
  var tmp = w[0];
  for (var i=0; i<3; i++) w[i] = w[i+1];
  w[3] = tmp;
  return w;
}

// Sbox is pre-computed multiplicative inverse in GF(2^8) used in SubBytes and KeyExpansion [§5.1.1]
Aes.Sbox =  [0x63,0x7c,0x77,0x7b,0xf2,0x6b,0x6f,0xc5,0x30,0x01,0x67,0x2b,0xfe,0xd7,0xab,0x76,
             0xca,0x82,0xc9,0x7d,0xfa,0x59,0x47,0xf0,0xad,0xd4,0xa2,0xaf,0x9c,0xa4,0x72,0xc0,
             0xb7,0xfd,0x93,0x26,0x36,0x3f,0xf7,0xcc,0x34,0xa5,0xe5,0xf1,0x71,0xd8,0x31,0x15,
             0x04,0xc7,0x23,0xc3,0x18,0x96,0x05,0x9a,0x07,0x12,0x80,0xe2,0xeb,0x27,0xb2,0x75,
             0x09,0x83,0x2c,0x1a,0x1b,0x6e,0x5a,0xa0,0x52,0x3b,0xd6,0xb3,0x29,0xe3,0x2f,0x84,
             0x53,0xd1,0x00,0xed,0x20,0xfc,0xb1,0x5b,0x6a,0xcb,0xbe,0x39,0x4a,0x4c,0x58,0xcf,
             0xd0,0xef,0xaa,0xfb,0x43,0x4d,0x33,0x85,0x45,0xf9,0x02,0x7f,0x50,0x3c,0x9f,0xa8,
             0x51,0xa3,0x40,0x8f,0x92,0x9d,0x38,0xf5,0xbc,0xb6,0xda,0x21,0x10,0xff,0xf3,0xd2,
             0xcd,0x0c,0x13,0xec,0x5f,0x97,0x44,0x17,0xc4,0xa7,0x7e,0x3d,0x64,0x5d,0x19,0x73,
             0x60,0x81,0x4f,0xdc,0x22,0x2a,0x90,0x88,0x46,0xee,0xb8,0x14,0xde,0x5e,0x0b,0xdb,
             0xe0,0x32,0x3a,0x0a,0x49,0x06,0x24,0x5c,0xc2,0xd3,0xac,0x62,0x91,0x95,0xe4,0x79,
             0xe7,0xc8,0x37,0x6d,0x8d,0xd5,0x4e,0xa9,0x6c,0x56,0xf4,0xea,0x65,0x7a,0xae,0x08,
             0xba,0x78,0x25,0x2e,0x1c,0xa6,0xb4,0xc6,0xe8,0xdd,0x74,0x1f,0x4b,0xbd,0x8b,0x8a,
             0x70,0x3e,0xb5,0x66,0x48,0x03,0xf6,0x0e,0x61,0x35,0x57,0xb9,0x86,0xc1,0x1d,0x9e,
             0xe1,0xf8,0x98,0x11,0x69,0xd9,0x8e,0x94,0x9b,0x1e,0x87,0xe9,0xce,0x55,0x28,0xdf,
             0x8c,0xa1,0x89,0x0d,0xbf,0xe6,0x42,0x68,0x41,0x99,0x2d,0x0f,0xb0,0x54,0xbb,0x16];

// Rcon is Round Constant used for the Key Expansion [1st col is 2^(r-1) in GF(2^8)] [§5.2]
Aes.Rcon = [ [0x00, 0x00, 0x00, 0x00],
             [0x01, 0x00, 0x00, 0x00],
             [0x02, 0x00, 0x00, 0x00],
             [0x04, 0x00, 0x00, 0x00],
             [0x08, 0x00, 0x00, 0x00],
             [0x10, 0x00, 0x00, 0x00],
             [0x20, 0x00, 0x00, 0x00],
             [0x40, 0x00, 0x00, 0x00],
             [0x80, 0x00, 0x00, 0x00],
             [0x1b, 0x00, 0x00, 0x00],
             [0x36, 0x00, 0x00, 0x00] ]; 


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  AES Counter-mode implementation in JavaScript (c) Chris Veness 2005-2010                      */
/*   - see http://csrc.nist.gov/publications/nistpubs/800-38a/sp800-38a.pdf                       */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

var AesCtr = {};  // AesCtr namespace

/** 
 * Encrypt a text using AES encryption in Counter mode of operation
 *
 * Unicode multi-byte character safe
 *
 * @param {String} plaintext Source text to be encrypted
 * @param {String} password  The password to use to generate a key
 * @param {Number} nBits     Number of bits to be used in the key (128, 192, or 256)
 * @returns {string}         Encrypted text
 */
AesCtr.encrypt = function(plaintext, password, nBits) {
  var blockSize = 16;  // block size fixed at 16 bytes / 128 bits (Nb=4) for AES
  if (!(nBits==128 || nBits==192 || nBits==256)) return '';  // standard allows 128/192/256 bit keys
  plaintext = Utf8.encode(plaintext);
  password = Utf8.encode(password);
  //var t = new Date();  // timer
        
  // use AES itself to encrypt password to get cipher key (using plain password as source for key 
  // expansion) - gives us well encrypted key
  var nBytes = nBits/8;  // no bytes in key
  var pwBytes = new Array(nBytes);
  for (var i=0; i<nBytes; i++) {
    pwBytes[i] = isNaN(password.charCodeAt(i)) ? 0 : password.charCodeAt(i);
  }
  var key = Aes.Cipher(pwBytes, Aes.KeyExpansion(pwBytes));  // gives us 16-byte key
  key = key.concat(key.slice(0, nBytes-16));  // expand key to 16/24/32 bytes long

  // initialise counter block (NIST SP800-38A §B.2): millisecond time-stamp for nonce in 1st 8 bytes,
  // block counter in 2nd 8 bytes
  var counterBlock = new Array(blockSize);
  var nonce = (new Date()).getTime();  // timestamp: milliseconds since 1-Jan-1970
  var nonceSec = Math.floor(nonce/1000);
  var nonceMs = nonce%1000;
  // encode nonce with seconds in 1st 4 bytes, and (repeated) ms part filling 2nd 4 bytes
  for (var i=0; i<4; i++) counterBlock[i] = (nonceSec >>> i*8) & 0xff;
  for (var i=0; i<4; i++) counterBlock[i+4] = nonceMs & 0xff; 
  // and convert it to a string to go on the front of the ciphertext
  var ctrTxt = '';
  for (var i=0; i<8; i++) ctrTxt += String.fromCharCode(counterBlock[i]);

  // generate key schedule - an expansion of the key into distinct Key Rounds for each round
  var keySchedule = Aes.KeyExpansion(key);
  
  var blockCount = Math.ceil(plaintext.length/blockSize);
  var ciphertxt = new Array(blockCount);  // ciphertext as array of strings
  
  for (var b=0; b<blockCount; b++) {
    // set counter (block #) in last 8 bytes of counter block (leaving nonce in 1st 8 bytes)
    // done in two stages for 32-bit ops: using two words allows us to go past 2^32 blocks (68GB)
    for (var c=0; c<4; c++) counterBlock[15-c] = (b >>> c*8) & 0xff;
    for (var c=0; c<4; c++) counterBlock[15-c-4] = (b/0x100000000 >>> c*8)

    var cipherCntr = Aes.Cipher(counterBlock, keySchedule);  // -- encrypt counter block --
    
    // block size is reduced on final block
    var blockLength = b<blockCount-1 ? blockSize : (plaintext.length-1)%blockSize+1;
    var cipherChar = new Array(blockLength);
    
    for (var i=0; i<blockLength; i++) {  // -- xor plaintext with ciphered counter char-by-char --
      cipherChar[i] = cipherCntr[i] ^ plaintext.charCodeAt(b*blockSize+i);
      cipherChar[i] = String.fromCharCode(cipherChar[i]);
    }
    ciphertxt[b] = cipherChar.join(''); 
  }

  // Array.join is more efficient than repeated string concatenation in IE
  var ciphertext = ctrTxt + ciphertxt.join('');
  ciphertext = Base64.encode(ciphertext);  // encode in base64
  
  //alert((new Date()) - t);
  return ciphertext;
}

/** 
 * Decrypt a text encrypted by AES in counter mode of operation
 *
 * @param {String} ciphertext Source text to be encrypted
 * @param {String} password   The password to use to generate a key
 * @param {Number} nBits      Number of bits to be used in the key (128, 192, or 256)
 * @returns {String}          Decrypted text
 */
AesCtr.decrypt = function(ciphertext, password, nBits) {
  var blockSize = 16;  // block size fixed at 16 bytes / 128 bits (Nb=4) for AES
  if (!(nBits==128 || nBits==192 || nBits==256)) return '';  // standard allows 128/192/256 bit keys
  ciphertext = Base64.decode(ciphertext);
  password = Utf8.encode(password);
  //var t = new Date();  // timer
  
  // use AES to encrypt password (mirroring encrypt routine)
  var nBytes = nBits/8;  // no bytes in key
  var pwBytes = new Array(nBytes);
  for (var i=0; i<nBytes; i++) {
    pwBytes[i] = isNaN(password.charCodeAt(i)) ? 0 : password.charCodeAt(i);
  }
  var key = Aes.Cipher(pwBytes, Aes.KeyExpansion(pwBytes));
  key = key.concat(key.slice(0, nBytes-16));  // expand key to 16/24/32 bytes long

  // recover nonce from 1st 8 bytes of ciphertext
  var counterBlock = new Array(8);
  ctrTxt = ciphertext.slice(0, 8);
  for (var i=0; i<8; i++) counterBlock[i] = ctrTxt.charCodeAt(i);
  
  // generate key schedule
  var keySchedule = Aes.KeyExpansion(key);

  // separate ciphertext into blocks (skipping past initial 8 bytes)
  var nBlocks = Math.ceil((ciphertext.length-8) / blockSize);
  var ct = new Array(nBlocks);
  for (var b=0; b<nBlocks; b++) ct[b] = ciphertext.slice(8+b*blockSize, 8+b*blockSize+blockSize);
  ciphertext = ct;  // ciphertext is now array of block-length strings

  // plaintext will get generated block-by-block into array of block-length strings
  var plaintxt = new Array(ciphertext.length);

  for (var b=0; b<nBlocks; b++) {
    // set counter (block #) in last 8 bytes of counter block (leaving nonce in 1st 8 bytes)
    for (var c=0; c<4; c++) counterBlock[15-c] = ((b) >>> c*8) & 0xff;
    for (var c=0; c<4; c++) counterBlock[15-c-4] = (((b+1)/0x100000000-1) >>> c*8) & 0xff;

    var cipherCntr = Aes.Cipher(counterBlock, keySchedule);  // encrypt counter block

    var plaintxtByte = new Array(ciphertext[b].length);
    for (var i=0; i<ciphertext[b].length; i++) {
      // -- xor plaintxt with ciphered counter byte-by-byte --
      plaintxtByte[i] = cipherCntr[i] ^ ciphertext[b].charCodeAt(i);
      plaintxtByte[i] = String.fromCharCode(plaintxtByte[i]);
    }
    plaintxt[b] = plaintxtByte.join('');
  }

  // join array of blocks into single plaintext string
  var plaintext = plaintxt.join('');
  plaintext = Utf8.decode(plaintext);  // decode from UTF8 back to Unicode multi-byte chars
  
  //alert((new Date()) - t);
  return plaintext;
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  Base64 class: Base 64 encoding / decoding (c) Chris Veness 2002-2010                          */
/*    note: depends on Utf8 class                                                                 */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

var Base64 = {};  // Base64 namespace

Base64.code = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

/**
 * Encode string into Base64, as defined by RFC 4648 [http://tools.ietf.org/html/rfc4648]
 * (instance method extending String object). As per RFC 4648, no newlines are added.
 *
 * @param {String} str The string to be encoded as base-64
 * @param {Boolean} [utf8encode=false] Flag to indicate whether str is Unicode string to be encoded 
 *   to UTF8 before conversion to base64; otherwise string is assumed to be 8-bit characters
 * @returns {String} Base64-encoded string
 */ 
Base64.encode = function(str, utf8encode) {  // http://tools.ietf.org/html/rfc4648
  utf8encode =  (typeof utf8encode == 'undefined') ? false : utf8encode;
  var o1, o2, o3, bits, h1, h2, h3, h4, e=[], pad = '', c, plain, coded;
  var b64 = Base64.code;
   
  plain = utf8encode ? str.encodeUTF8() : str;
  
  c = plain.length % 3;  // pad string to length of multiple of 3
  if (c > 0) { while (c++ < 3) { pad += '='; plain += '\0'; } }
  // note: doing padding here saves us doing special-case packing for trailing 1 or 2 chars
   
  for (c=0; c<plain.length; c+=3) {  // pack three octets into four hexets
    o1 = plain.charCodeAt(c);
    o2 = plain.charCodeAt(c+1);
    o3 = plain.charCodeAt(c+2);
      
    bits = o1<<16 | o2<<8 | o3;
      
    h1 = bits>>18 & 0x3f;
    h2 = bits>>12 & 0x3f;
    h3 = bits>>6 & 0x3f;
    h4 = bits & 0x3f;

    // use hextets to index into code string
    e[c/3] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
  }
  coded = e.join('');  // join() is far faster than repeated string concatenation in IE
  
  // replace 'A's from padded nulls with '='s
  coded = coded.slice(0, coded.length-pad.length) + pad;
   
  return coded;
}

/**
 * Decode string from Base64, as defined by RFC 4648 [http://tools.ietf.org/html/rfc4648]
 * (instance method extending String object). As per RFC 4648, newlines are not catered for.
 *
 * @param {String} str The string to be decoded from base-64
 * @param {Boolean} [utf8decode=false] Flag to indicate whether str is Unicode string to be decoded 
 *   from UTF8 after conversion from base64
 * @returns {String} decoded string
 */ 
Base64.decode = function(str, utf8decode) {
  utf8decode =  (typeof utf8decode == 'undefined') ? false : utf8decode;
  var o1, o2, o3, h1, h2, h3, h4, bits, d=[], plain, coded;
  var b64 = Base64.code;

  coded = utf8decode ? str.decodeUTF8() : str;
  
  
  for (var c=0; c<coded.length; c+=4) {  // unpack four hexets into three octets
    h1 = b64.indexOf(coded.charAt(c));
    h2 = b64.indexOf(coded.charAt(c+1));
    h3 = b64.indexOf(coded.charAt(c+2));
    h4 = b64.indexOf(coded.charAt(c+3));
      
    bits = h1<<18 | h2<<12 | h3<<6 | h4;
      
    o1 = bits>>>16 & 0xff;
    o2 = bits>>>8 & 0xff;
    o3 = bits & 0xff;
    
    d[c/4] = String.fromCharCode(o1, o2, o3);
    // check for padding
    if (h4 == 0x40) d[c/4] = String.fromCharCode(o1, o2);
    if (h3 == 0x40) d[c/4] = String.fromCharCode(o1);
  }
  plain = d.join('');  // join() is far faster than repeated string concatenation in IE
   
  return utf8decode ? plain.decodeUTF8() : plain; 
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  Utf8 class: encode / decode between multi-byte Unicode characters and UTF-8 multiple          */
/*              single-byte character encoding (c) Chris Veness 2002-2010                         */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

var Utf8 = {};  // Utf8 namespace

/**
 * Encode multi-byte Unicode string into utf-8 multiple single-byte characters 
 * (BMP / basic multilingual plane only)
 *
 * Chars in range U+0080 - U+07FF are encoded in 2 chars, U+0800 - U+FFFF in 3 chars
 *
 * @param {String} strUni Unicode string to be encoded as UTF-8
 * @returns {String} encoded string
 */
Utf8.encode = function(strUni) {
  // use regular expressions & String.replace callback function for better efficiency 
  // than procedural approaches
  var strUtf = strUni.replace(
      /[\u0080-\u07ff]/g,  // U+0080 - U+07FF => 2 bytes 110yyyyy, 10zzzzzz
      function(c) { 
        var cc = c.charCodeAt(0);
        return String.fromCharCode(0xc0 | cc>>6, 0x80 | cc&0x3f); }
    );
  strUtf = strUtf.replace(
      /[\u0800-\uffff]/g,  // U+0800 - U+FFFF => 3 bytes 1110xxxx, 10yyyyyy, 10zzzzzz
      function(c) { 
        var cc = c.charCodeAt(0); 
        return String.fromCharCode(0xe0 | cc>>12, 0x80 | cc>>6&0x3F, 0x80 | cc&0x3f); }
    );
  return strUtf;
}

/**
 * Decode utf-8 encoded string back into multi-byte Unicode characters
 *
 * @param {String} strUtf UTF-8 string to be decoded back to Unicode
 * @returns {String} decoded string
 */
Utf8.decode = function(strUtf) {
  var strUni = strUtf.replace(
      /[\u00c0-\u00df][\u0080-\u00bf]/g,                 // 2-byte chars
      function(c) {  // (note parentheses for precence)
        var cc = (c.charCodeAt(0)&0x1f)<<6 | c.charCodeAt(1)&0x3f;
        return String.fromCharCode(cc); }
    );
  strUni = strUni.replace(
      /[\u00e0-\u00ef][\u0080-\u00bf][\u0080-\u00bf]/g,  // 3-byte chars
      function(c) {  // (note parentheses for precence)
        var cc = ((c.charCodeAt(0)&0x0f)<<12) | ((c.charCodeAt(1)&0x3f)<<6) | ( c.charCodeAt(2)&0x3f); 
        return String.fromCharCode(cc); }
    );
  return strUni;
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
;

/**
 *  @package    AkeebaCMSUpdate
 *  @copyright  Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license    GNU General Public License version 3, or later
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

var stat_total = 0;
var stat_files = 0;
var stat_inbytes = 0;
var stat_outbytes = 0;

/**
 * An extremely simple error handler, dumping error messages to screen
 *
 * @param error The error message string
 */
function error_callback(error)
{
	alert("ERROR:\n"+error);
}

/**
 * Performs an encrypted AJAX request and returns the parsed JSON output.
 * The window.ajax_url is used as the AJAX proxy URL.
 * If there is no errorCallback, the window.error_callback is used.
 *
 * @param   object    data             An object with the query data, e.g. a serialized form
 * @param   function  successCallback  A function accepting a single object parameter, called on success
 * @param   function  errorCallback    A function accepting a single string parameter, called on failure
 */
doEncryptedAjax = function(data, successCallback, errorCallback)
{
	var json = JSON.stringify(data);
	if( joomlaupdate_password.length > 0 )
	{
		json = AesCtr.encrypt( json, joomlaupdate_password, 128 );
	}
	var post_data = {
		'json':     json
	};

	var structure =
	{
		type: "POST",
		url: joomlaupdate_ajax_url,
		cache: false,
		data: post_data,
		timeout: 600000,

		success: function(msg, responseXML)
		{
			// Initialize
			var junk = null;
			var message = "";

			// Get rid of junk before the data
			var valid_pos = msg.indexOf('###');

			if( valid_pos == -1 )
			{
				// Valid data not found in the response
				msg = 'Invalid AJAX data:\n' + msg;

				if (errorCallback == null)
				{
					if(error_callback != null)
					{
						error_callback(msg);
					}
				}
				else
				{
					errorCallback(msg);
				}

				return;
			}
			else if( valid_pos != 0 )
			{
				// Data is prefixed with junk
				junk = msg.substr(0, valid_pos);
				message = msg.substr(valid_pos);
			}
			else
			{
				message = msg;
			}

			message = message.substr(3); // Remove triple hash in the beginning

			// Get of rid of junk after the data
			var valid_pos = message.lastIndexOf('###');

			message = message.substr(0, valid_pos); // Remove triple hash in the end

			// Decrypt if required
			var data = null;
			if( joomlaupdate_password.length > 0 )
			{
				try
				{
					var data = JSON.parse(message);
				}
				catch(err)
				{
					message = AesCtr.decrypt(message, joomlaupdate_password, 128);
				}
			}

			try
			{
				if (empty(data))
				{
					data = JSON.parse(message);
				}
			}
			catch(err)
			{
				var msg = err.message + "\n<br/>\n<pre>\n" + message + "\n</pre>";

				if (errorCallback == null)
				{
					if (error_callback != null)
					{
						error_callback(msg);
					}
				}
				else
				{
					errorCallback(msg);
				}

				return;
			}

			// Call the callback function
			successCallback(data);
		},

		error: function(req)
		{
			var message = 'AJAX Loading Error: ' + req.statusText;

			if(errorCallback == null)
			{
				if (error_callback != null)
				{
					error_callback(message);
				}
			}
			else
			{
				errorCallback(message);
			}
		}
	};

	jQuery.ajax( structure );
};

/**
 * Pings the update script (making sure its executable)
 */
pingExtract = function()
{
	// Reset variables
	this.stat_files = 0;
	this.stat_inbytes = 0;
	this.stat_outbytes = 0;

	// Do AJAX post
	var post = {task : 'ping'};

	this.doEncryptedAjax(post,
		function(data) {
			startExtract(data);
		});
};

startExtract = function()
{
	// Reset variables
	this.stat_files = 0;
	this.stat_inbytes = 0;
	this.stat_outbytes = 0;

	var post = { task : 'startRestore' };

	this.doEncryptedAjax(post, function(data){
		stepExtract(data);
	});
};

stepExtract = function(data)
{
	if(data.status == false)
	{
		// handle failure
		error_callback(data.message);

		return;
	}

	if( !empty(data.Warnings) )
	{
		// @todo Handle warnings
		/**
		 $.each(data.Warnings, function(i, item){
            $('#warnings').append(
                $(document.createElement('div'))
                    .html(item)
            );
            $('#warningsBox').show('fast');
        });
		 /**/
	}

	if (!empty(data.factory))
	{
		extract_factory = data.factory;
	}

	if(data.done)
	{
		finalizeUpdate();
	}
	else
	{
		// Add data to variables
		stat_inbytes += data.bytesIn;
		stat_percent = (stat_inbytes * 100) / joomlaupdate_totalsize;

		// Update GUI
		stat_outbytes += data.bytesOut;
		stat_files += data.files;

		if (stat_percent < 100)
		{
			jQuery('#progress-bar').css('width', stat_percent + '%').attr('aria-valuenow', stat_percent);
		}
		else if (stat_percent > 100)
		{
			stat_percent = 100;
			jQuery('#progress-bar').css('width', stat_percent + '%').attr('aria-valuenow', stat_percent);
		}
		else
		{
			jQuery('#progress-bar').removeClass('bar-success');
		}

		jQuery('#extpercent').text(stat_percent.toFixed(1) + '%');
		jQuery('#extbytesin').text(stat_inbytes);
		jQuery('#extbytesout').text(stat_outbytes);
		jQuery('#extfiles').text(stat_files);

		// Do AJAX post
		post = {
			task: 'stepRestore',
			factory: data.factory
		};
		doEncryptedAjax(post, function(data){
			stepExtract(data);
		});
	}
};

finalizeUpdate = function ()
{
	// Do AJAX post
	var post = { task : 'finalizeRestore', factory: window.factory };
	doEncryptedAjax(post, function(data){
		window.location = joomlaupdate_return_url;
	});
};


/**
 * Is a variable empty?
 *
 * Part of php.js
 *
 * @see  http://phpjs.org/
 *
 * @param   mixed  mixed_var  The variable
 *
 * @returns  boolean  True if empty
 */
function empty (mixed_var)
{
	var key;

	if (mixed_var === "" ||
		mixed_var === 0 ||
		mixed_var === "0" ||
		mixed_var === null ||
		mixed_var === false ||
		typeof mixed_var === 'undefined'
	){
		return true;
	}

	if (typeof mixed_var == 'object')
	{
		for (key in mixed_var)
		{
			return false;
		}

		return true;
	}

	return false;
}

/**
 * Is the variable an array?
 *
 * Part of php.js
 *
 * @see  http://phpjs.org/
 *
 * @param   mixed  mixed_var  The variable
 *
 * @returns  boolean  True if it is an array or an object
 */
function is_array (mixed_var)
{
	var key = '';
	var getFuncName = function (fn) {
		var name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);

		if (!name) {
			return '(Anonymous)';
		}

		return name[1];
	};

	if (!mixed_var)
	{
		return false;
	}

	// BEGIN REDUNDANT
	this.php_js = this.php_js || {};
	this.php_js.ini = this.php_js.ini || {};
	// END REDUNDANT

	if (typeof mixed_var === 'object')
	{
		if (this.php_js.ini['phpjs.objectsAsArrays'] &&  // Strict checking for being a JavaScript array (only check this way if call ini_set('phpjs.objectsAsArrays', 0) to disallow objects as arrays)
			(
			(this.php_js.ini['phpjs.objectsAsArrays'].local_value.toLowerCase &&
			this.php_js.ini['phpjs.objectsAsArrays'].local_value.toLowerCase() === 'off') ||
			parseInt(this.php_js.ini['phpjs.objectsAsArrays'].local_value, 10) === 0)
		) {
			return mixed_var.hasOwnProperty('length') && // Not non-enumerable because of being on parent class
			!mixed_var.propertyIsEnumerable('length') && // Since is own property, if not enumerable, it must be a built-in function
			getFuncName(mixed_var.constructor) !== 'String'; // exclude String()
		}

		if (mixed_var.hasOwnProperty)
		{
			for (key in mixed_var) {
				// Checks whether the object has the specified property
				// if not, we figure it's not an object in the sense of a php-associative-array.
				if (false === mixed_var.hasOwnProperty(key)) {
					return false;
				}
			}
		}

		// Read discussion at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_is_array/
		return true;
	}

	return false;
}
;

function extractionMethodHandler(target, prefix)
{
	jQuery(function ($) {
		$em = $(target);
		displayStyle = ($em.val() === 'direct') ? 'none' : 'table-row';

		document.getElementById(prefix + '_notice').style.display = displayStyle;
		document.getElementById(prefix + '_hostname').style.display = displayStyle;
		document.getElementById(prefix + '_port').style.display = displayStyle;
		document.getElementById(prefix + '_username').style.display = displayStyle;
		document.getElementById(prefix + '_password').style.display = displayStyle;
		document.getElementById(prefix + '_directory').style.display = displayStyle;
	});
}

(function($, document, window) {
    /**
     * PreUpdateChecker
     *
     * @type {Object}
     */
    var PreUpdateChecker = {};

	/**
	 * Warning visibility flags
	 *
	 * @type {Boolean}
	 */
	var showorangewarning = false;
	var showyellowwarning = false;

	/**
     * Config object
     *
     * @type {{serverUrl: string, selector: string}}
     */
    PreUpdateChecker.config = {
        serverUrl: 'index.php?option=com_joomlaupdate&task=update.fetchextensioncompatibility',
        selector: '.extension-check'
    };

    /**
     * Extension compatibility states returned by the server.
     *
     * @type {{INCOMPATIBLE: number, COMPATIBLE: number, MISSING_COMPATIBILITY_TAG: number, SERVER_ERROR: number}}
     */
    PreUpdateChecker.STATE = {
        INCOMPATIBLE: 0,
        COMPATIBLE: 1,
        MISSING_COMPATIBILITY_TAG: 2,
        SERVER_ERROR: 3
    };

    /**
     * Run the PreUpdateChecker.
     * Called by document ready, setup below.
     */
    PreUpdateChecker.run = function () {

		// The main page only: this file is also the update page's
		if (!document.getElementById('joomlaupdate-wrapper'))
		{
			return;
		}

		$('.settingstoggle').css('float', 'right').css('cursor', 'pointer');
		$('.settingstoggle').on('click', function(toggle, index)
		{
			var settingsfieldset = $(this).closest('fieldset');
			if($(this).data('state') == 'closed')
			{
				$(this).data('state', 'open');
				$(this).html( COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSIONS_SHOW_LESS_COMPATIBILITY_INFORMATION);
				settingsfieldset.find('.settingsInfo').removeClass('hidden');
			}
			else
			{
				$(this).data('state', 'closed');
				$(this).html( COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSIONS_SHOW_MORE_COMPATIBILITY_INFORMATION);
				settingsfieldset.find('.settingsInfo').addClass('hidden');
			}
		});

		PreUpdateChecker.nonCoreCriticalPlugins = Joomla.getOptions('nonCoreCriticalPlugins', []);

		// If there are no non Core Critical Plugins installed then disable the warnings upfront
		if (PreUpdateChecker.nonCoreCriticalPlugins.length === 0)
		{
			$('#preupdateCheckWarning, #preupdateconfirmation, #preupdatecheckbox, #preupdatecheckheadings').css('display', 'none');
			$('#preupdatecheckbox #noncoreplugins').prop('checked', true);
			$('button.submitupdate').removeClass('disabled');
			$('button.submitupdate').prop('disabled', false);
		}

		// Grab all extensions based on the selector set in the config object
		var $extensions = $(PreUpdateChecker.config.selector);

		// If there are no extensions to be checked we can exit here
		if ($extensions.length === 0)
		{
			return;
		}

		$('#preupdatecheckbox #noncoreplugins').on('change', function ()
		{
			if ($('#preupdatecheckbox #noncoreplugins').is(':checked')) {
				if (confirm(Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_POTENTIALLY_DANGEROUS_PLUGIN_CONFIRM_MESSAGE'))) {
					$('button.submitupdate').removeClass('disabled');
					$('button.submitupdate').prop('disabled', false);
				}
				else
				{
					$('#preupdatecheckbox #noncoreplugins').prop('checked', false);
				}
			} else {
				$('button.submitupdate').addClass('disabled');
				$('button.submitupdate').prop('disabled', true);
			}

		});

		// Get version of the available joomla update
        PreUpdateChecker.joomlaTargetVersion = window.joomlaTargetVersion;
        PreUpdateChecker.joomlaCurrentVersion = window.joomlaCurrentVersion;

		// No point creating and loading a component stylesheet for 4 settings
		$('.compatibilitytypes img').css('height', '20px');
		$('.compatibilitytypes').css('display', 'none').css('margin-left', 0);
		// The currently processing line should show until it’s finished
		$('#compatibilitytype0').css('display', 'block');
		$('.compatibilitytoggle').css('float', 'right').css('cursor', 'pointer');

		$('.compatibilitytoggle').on('click', function(toggle, index)
		{
			var compatibilitytypes = $(this).closest('fieldset.compatibilitytypes');
			if($(this).data('state') == 'closed')
			{
				$(this).data('state', 'open');
				$(this).html( COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSIONS_SHOW_LESS_COMPATIBILITY_INFORMATION);
				compatibilitytypes.find('.exname').removeClass('span8').addClass('span4');
				compatibilitytypes.find('.extype').removeClass('span4').addClass('span1');
				compatibilitytypes.find('.upcomp').removeClass('hidden').addClass('span3');
				compatibilitytypes.find('.currcomp').removeClass('hidden').addClass('span3');
				compatibilitytypes.find('.instver').removeClass('hidden').addClass('span1');

				if (PreUpdateChecker.showyellowwarning)
				{
					compatibilitytypes.find("#updateyellowwarning").removeClass('hidden');
				}
				if (PreUpdateChecker.showorangewarning)
				{
					compatibilitytypes.find("#updateorangewarning").removeClass('hidden');
				}
			}
			else
			{
				$(this).data('state', 'closed');
				$(this).html( COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSIONS_SHOW_MORE_COMPATIBILITY_INFORMATION);
				compatibilitytypes.find('.exname').addClass('span8').removeClass('span4');
				compatibilitytypes.find('.extype').addClass('span4').removeClass('span1');
				compatibilitytypes.find('.upcomp').addClass('hidden').removeClass('span3');
				compatibilitytypes.find('.currcomp').addClass('hidden').removeClass('span3');
				compatibilitytypes.find('.instver').addClass('hidden').removeClass('span1');

				compatibilitytypes.find("#updateyellowwarning").addClass('hidden');
				compatibilitytypes.find("#updateorangewarning").addClass('hidden');
			}
		});

        $extensions.each(function () {
            // Check compatibility for each extension, pass jQuery object and a callback
            // function after completing the request
            PreUpdateChecker.checkCompatibility($(this), PreUpdateChecker.setResultView);
        });
    }

    /**
     * Check the compatibility for a single extension.
     * Requests the server checking the compatibility based on the data set in the element's data attributes.
     *
     * @param {Object} $extension
     * @param {callable} callback
     */
    PreUpdateChecker.checkCompatibility = function ($extension, callback) {
        // Result object passed to the callback
        // Set to server error by default
        var extension = {
            $element: $extension,
            compatibleVersion: 0,
            serverError: 1
        };

        // Request the server to check the compatiblity for the passed extension and joomla version
        $.getJSON(PreUpdateChecker.config.serverUrl, {
            'joomla-target-version': PreUpdateChecker.joomlaTargetVersion,
            'joomla-current-version': PreUpdateChecker.joomlaCurrentVersion,
            'extension-version': $extension.data('extension-current-version'),
            'extension-id': $extension.data('extensionId')
        }).done(function(response) {
            // Extract the data from the JResponseJson object
            extension.serverError = 0;
            extension.compatibilityData = response.data;
        }).always(function(e) {
            // Pass the retrieved data to the callback
            callback(extension);
        });
    }

    /**
     * Set the result for a passed extensionData object containing state, jQuery object and compatible version
     *
     * @param {Object} extensionData
     */
    PreUpdateChecker.setResultView = function (extensionData) {
        var html = '';

        // Process Target Version Extension Compatibility
        if (extensionData.serverError) {
			// An error occurred -> show unknown error note
			html = Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_SERVER_ERROR');
			// force result into group 4 = Pre update checks failed
			extensionData.compatibilityData = {
				'resultGroup' : 4
			}
		}
        else {
			// Switch the compatibility state
			switch (extensionData.compatibilityData.upgradeCompatibilityStatus.state) {
				case PreUpdateChecker.STATE.COMPATIBLE:
					if (extensionData.compatibilityData.upgradeWarning)
					{
						html = '<span class="label label-warning">' + extensionData.compatibilityData.upgradeCompatibilityStatus.compatibleVersion + '</span>';
						PreUpdateChecker.showyellowwarning = true;
					}
					else {
						html = extensionData.compatibilityData.upgradeCompatibilityStatus.compatibleVersion == false ? Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_NO_COMPATIBILITY_INFORMATION') : extensionData.compatibilityData.upgradeCompatibilityStatus.compatibleVersion;
					}
					break;
				case PreUpdateChecker.STATE.INCOMPATIBLE:
					// No compatible version found -> display error label
					html = Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_NO_COMPATIBILITY_INFORMATION');
					PreUpdateChecker.showorangewarning = true;
					break;
				case PreUpdateChecker.STATE.MISSING_COMPATIBILITY_TAG:
					// Could not check compatibility state -> display warning
					html = Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_NO_COMPATIBILITY_INFORMATION');
					PreUpdateChecker.showorangewarning = true;
					break;
				default:
					// An error occured -> show unknown error note
					html = Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_WARNING_UNKNOWN');
			}
		}
        // Insert the generated html
        extensionData.$element.html(html);

		// Process Current Version Extension Compatibility
		html = '';
		if (extensionData.serverError) {
			// An error occured -> show unknown error note
			html = Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_SERVER_ERROR');
		}
		else {
			// Switch the compatibility state
			switch (extensionData.compatibilityData.currentCompatibilityStatus.state) {
				case PreUpdateChecker.STATE.COMPATIBLE:
					html = extensionData.compatibilityData.currentCompatibilityStatus.compatibleVersion == false ? Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_NO_COMPATIBILITY_INFORMATION') : extensionData.compatibilityData.currentCompatibilityStatus.compatibleVersion;
					break;
				case PreUpdateChecker.STATE.INCOMPATIBLE:
					// No compatible version found -> display error label
					html = Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_NO_COMPATIBILITY_INFORMATION');
					break;
				case PreUpdateChecker.STATE.MISSING_COMPATIBILITY_TAG:
					// Could not check compatibility state -> display warning
					html = Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_NO_COMPATIBILITY_INFORMATION');
					break;
				default:
					// An error occured -> show unknown error note
					html = Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_EXTENSION_WARNING_UNKNOWN');
			}
		}
		// Insert the generated html
		var extensionId = extensionData.$element.data('extensionId')
		document.getElementById('available-version-' + extensionId ).innerHTML = html;

		extensionData.$element.closest('tr').appendTo($('#compatibilitytype' + extensionData.compatibilityData.resultGroup + ' tbody'));
		$('#compatibilitytype' + extensionData.compatibilityData.resultGroup).css('display', 'block');

		document.getElementById('compatibilitytype0').style.display = 'block';

		// Process the nonCoreCriticalPlugin list
		if (extensionData.compatibilityData.resultGroup === 3)
		{
			var pluginInfo;

			for (var i = PreUpdateChecker.nonCoreCriticalPlugins.length - 1; i >= 0; i--)
			{
			  pluginInfo = PreUpdateChecker.nonCoreCriticalPlugins[i];

			  if (pluginInfo.package_id == extensionId || pluginInfo.extension_id == extensionId)
			  {
				$('#plg_' + pluginInfo.extension_id).remove();
				PreUpdateChecker.nonCoreCriticalPlugins.splice(i, 1);
			  }
			}
		}

		// Have we finished running through the potentially critical plugins - if so we can hide the warning before all the checks are completed
		if ($('#preupdatecheckheadings table td').length == 0) {
			$('#preupdatecheckheadings').css('display', 'none');
		}

		// Have we finished?
		if ($('#compatibilitytype0 tbody td').length == 0) {
			$('#compatibilitytype0').css('display', 'none');
			for (var cpi in PreUpdateChecker.nonCoreCriticalPlugins)
			{
				var problemPluginRow = $('td[data-extension-id=' + PreUpdateChecker.nonCoreCriticalPlugins[cpi].extension_id +']');
				if (!problemPluginRow.length)
				{
					problemPluginRow = $('td[data-extension-id=' + PreUpdateChecker.nonCoreCriticalPlugins[cpi].package_id +']');
				}
				if (problemPluginRow.length)
				{
					var tableRow = problemPluginRow.closest('tr');
					tableRow.addClass('error');
					var pluginTitleTableCell = tableRow.find('td:first-child');
					pluginTitleTableCell.html(pluginTitleTableCell.html()
						+ '<span class="label label-warning " >'
						+ '<span class="icon-warning"></span>'
						+ Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_POTENTIALLY_DANGEROUS_PLUGIN')
						+ '</span>'

						+ '<span class="label label-info hasPopover" '
						+ ' title="' + Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_POTENTIALLY_DANGEROUS_PLUGIN') +'"'
						+ ' data-content="' + Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_POTENTIALLY_DANGEROUS_PLUGIN_DESC')  +'"'
						+ '>'
						+ '<span class="icon-help"></span>'
						+ Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_HELP')
						+ '</span>'
					);
					var popoverElement = pluginTitleTableCell.find('.hasPopover');
					popoverElement.css('cursor', 'pointer')
					popoverElement.popover({"placement": "top","trigger": "focus click"});
				}
			}
			if (PreUpdateChecker.nonCoreCriticalPlugins.length == 0)
			{
				$('#preupdateCheckWarning, #preupdateconfirmation, #preupdatecheckbox, #preupdatecheckheadings').css('display', 'none');
				$('#preupdatecheckbox #noncoreplugins').prop('checked', true);
				$('button.submitupdate').removeClass('disabled');
				$('button.submitupdate').prop('disabled', false);
			}
			else {
				$('#preupdateCheckWarning').addClass('hidden');
				$('#preupdateCheckCompleteProblems').removeClass('hidden');
				$('#preupdateconfirmation .preupdateconfirmation_label h3').html(Joomla.JText._('COM_JOOMLAUPDATE_VIEW_DEFAULT_POTENTIALLY_DANGEROUS_PLUGIN_LIST'))
				$('#preupdateconfirmation .preupdateconfirmation_label').removeClass('label-warning').addClass('label-important')
			}
		}
    }

    // Run PreUpdateChecker on document ready
	document.addEventListener( "DOMContentLoaded", function() {
		$(PreUpdateChecker.run);
	} );
})(jQuery, document, window);
