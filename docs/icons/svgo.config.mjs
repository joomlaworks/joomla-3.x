// svgo's settings for the distribution's SVG files (svgo --config docs/icons/svgo.config.mjs <files>): the default preset
// in several passes. svgo 4's preset keeps the viewBox, which the icons scale by (svgo 3's removed it: don't use svgo 3)
export default {
	multipass: true,
	plugins: ['preset-default'],
};
