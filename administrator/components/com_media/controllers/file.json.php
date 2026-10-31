<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

/**
 * File Media Controller
 *
 * @since  1.6
 */
class MediaControllerFile extends JControllerLegacy
{
    /**
     * Upload a file
     *
     * @return  void
     *
     * @since   1.5
     */
    public function upload()
    {
        $params = JComponentHelper::getParams('com_media');

        // Check for request forgeries
        if (!JSession::checkToken('request')) {
            $response = array(
                'status'  => '0',
                'message' => JText::_('JINVALID_TOKEN'),
                'error'   => JText::_('JINVALID_TOKEN')
            );
            echo json_encode($response);

            return;
        }

        // Get the user
        $user  = JFactory::getUser();
        JLog::addLogger(array('text_file' => 'upload.error.php'), JLog::ALL, array('upload'));

        // Get some data from the request
        $file   = $this->input->files->get('Filedata', '', 'array');
        $folder = $this->input->get('folder', '', 'path');

        // Files dropped on the Media Manager's lists (and the image picker): into the folder shown, as its upload form does
        $mediaManager = $this->input->getCmd('context') === 'mediamanager';

        // Instantiate the media helper
        $mediaHelper = new JHelperMedia();

        // The "Memory Limit" option, where the server allows it (before the limits below are read)
        $mediaHelper->raiseMemoryLimit($params);

        // Editors upload here (TinyMCE, EasyMDE): into uploads when they name no folder, and into the year, month or day's
        // folder as the "Organise Editor Uploads" option says (created only for users who may upload)
        if (!$mediaManager && $user->authorise('core.create', 'com_media')) {
            $folder = $mediaHelper->getEditorUploadFolder($folder, $params, COM_MEDIA_BASE);
        }

        // Limits of 0 or -1 (the Media Manager's "no limit", PHP's unlimited memory_limit or post_max_size) don't apply, as in file.php
        $contentLength = (int) $this->input->server->get('CONTENT_LENGTH', 0, 'INT');
        $tooLarge      = false;

        foreach (array(
            $params->get('upload_maxsize', 0) * 1024 * 1024,
            $mediaHelper->toBytes(ini_get('upload_max_filesize')),
            $mediaHelper->toBytes(ini_get('post_max_size')),
            $mediaHelper->toBytes(ini_get('memory_limit')),
        ) as $limit) {
            $tooLarge = $tooLarge || ((int) $limit > 0 && $contentLength > (int) $limit);
        }

        if ($tooLarge) {
            $response = array(
                'status'  => '0',
                'message' => JText::_('COM_MEDIA_ERROR_WARNFILETOOLARGE'),
                'error'   => JText::_('COM_MEDIA_ERROR_WARNFILETOOLARGE')
            );
            echo json_encode($response);

            return;
        }

        // Set FTP credentials, if given
        JClientHelper::setCredentialsFromRequest('ftp');

        if (isset($file['name'])) {
            // Make the filename safe
            $file['name'] = JFile::makeSafe($file['name']);

            // We need a URL safe name
            $fileparts = pathinfo(COM_MEDIA_BASE . '/' . $folder . '/' . $file['name']);

            // Transform filename to punycode
            $fileparts['filename'] = JStringPunycode::toPunycode($fileparts['filename']);
            $tempExt = !empty($fileparts['extension']) ? strtolower($fileparts['extension']) : '';

            // Transform filename to punycode, then neglect other than non-alphanumeric characters & underscores. Also transform extension to lowercase
            $safeFileName = preg_replace(array("/[\\s]/", '/[^a-zA-Z0-9_\-]/'), array('_', ''), $fileparts['filename']) . '.' . $tempExt;

            // Create filepath with safe-filename
            $files['final'] = $fileparts['dirname'] . DIRECTORY_SEPARATOR . $safeFileName;
            $file['name']   = $safeFileName;

            $filepath = JPath::clean($files['final']);

            // A file of the same name is kept: this one gets the time added to its name
            $filepath     = $mediaHelper->getUniqueFilePath($filepath);
            $file['name'] = basename($filepath);

            if (!$mediaHelper->canUpload($file, 'com_media')
                || strpos(realpath($fileparts['dirname']), JPath::clean(realpath(COM_MEDIA_BASE))) !== 0) {
                try {
                    JLog::add('Invalid: ' . $filepath, JLog::INFO, 'upload');
                } catch (RuntimeException $exception) {
                    // Informational log only
                }

                $response = array(
                    'status'  => '0',
                    'message' => JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE'),
                    'error'   => JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE')
                );

                echo json_encode($response);

                return;
            }

            // Trigger the onContentBeforeSave event.
            JPluginHelper::importPlugin('content');
            $dispatcher  = JEventDispatcher::getInstance();
            $object_file = new JObject($file);
            $object_file->filepath = $filepath;
            $result = $dispatcher->trigger('onContentBeforeSave', array('com_media.file', &$object_file, true));

            if (in_array(false, $result, true)) {
                // There are some errors in the plugins
                try {
                    JLog::add(
                        'Errors before save: ' . $object_file->filepath . ' : ' . implode(', ', $object_file->getErrors()),
                        JLog::INFO,
                        'upload'
                    );
                } catch (RuntimeException $exception) {
                    // Informational log only
                }

                $response = array(
                    'status'  => '0',
                    'message' => JText::plural('COM_MEDIA_ERROR_BEFORE_SAVE', count($errors = $object_file->getErrors()), implode('<br />', $errors)),
                    'error'   => JText::plural('COM_MEDIA_ERROR_BEFORE_SAVE', count($errors = $object_file->getErrors()), implode('<br />', $errors))
                );

                echo json_encode($response);

                return;
            }

            if (JFile::exists($object_file->filepath)) {
                // File exists
                try {
                    JLog::add('File exists: ' . $object_file->filepath . ' by user_id ' . $user->id, JLog::INFO, 'upload');
                } catch (RuntimeException $exception) {
                    // Informational log only
                }

                $response = array(
                    'status'   => '0',
                    'message'  => JText::_('COM_MEDIA_ERROR_FILE_EXISTS'),
                    'error'    => JText::_('COM_MEDIA_ERROR_FILE_EXISTS'),
                    'location' => str_replace(JPATH_ROOT, '', $filepath)
                );

                echo json_encode($response);

                return;
            } elseif (!$user->authorise('core.create', 'com_media')) {
                // File does not exist and user is not authorised to create
                try {
                    JLog::add('Create not permitted: ' . $object_file->filepath . ' by user_id ' . $user->id, JLog::INFO, 'upload');
                } catch (RuntimeException $exception) {
                    // Informational log only
                }

                $response = array(
                    'status'  => '0',
                    'error'   => JText::_('COM_MEDIA_ERROR_CREATE_NOT_PERMITTED'),
                    'message' => JText::_('COM_MEDIA_ERROR_CREATE_NOT_PERMITTED')
                );

                echo json_encode($response);

                return;
            }

            if (!JFile::upload($object_file->tmp_name, $object_file->filepath)) {
                // Error in upload
                try {
                    JLog::add('Error on upload: ' . $object_file->filepath, JLog::INFO, 'upload');
                } catch (RuntimeException $exception) {
                    // Informational log only
                }

                $response = array(
                    'status'  => '0',
                    'message' => JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE'),
                    'error'   => JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE')
                );

                echo json_encode($response);

                return;
            } else {
                // Trigger the onContentAfterSave event.
                $dispatcher->trigger('onContentAfterSave', array('com_media.file', &$object_file, true));

                try {
                    JLog::add($folder, JLog::INFO, 'upload');
                } catch (RuntimeException $exception) {
                    // Informational log only
                }

                // Resized, upright, without metadata, WebP and a copy for small screens, as the Media Manager's options say
                $sources = array();

                if ($mediaHelper->isOptimiseActive($params, $mediaManager ? 'mediamanager' : 'editors')) {
                    $settings              = $mediaHelper->getOptimiseSettings($params);
                    $object_file->filepath = $mediaHelper->optimiseImage($object_file->filepath, $settings);
                    $sources               = $mediaHelper->getImageSources($object_file->filepath, $settings);
                }

                $returnUrl = str_replace(JPATH_ROOT, '', $object_file->filepath);

                $response = array(
                    'status'   => '1',
                    'message'  => JText::sprintf('COM_MEDIA_UPLOAD_COMPLETE', $returnUrl),
                    'error'    => JText::sprintf('COM_MEDIA_UPLOAD_COMPLETE', $returnUrl),
                    'location' => str_replace('\\', '/', $returnUrl),
                    // Relative to the Media Manager's folder, as its lists name files (optimising may rename it, e.g. to .webp)
                    'path'     => ltrim(str_replace(array(JPath::clean(COM_MEDIA_BASE), '\\'), array('', '/'), JPath::clean($object_file->filepath)), '/'),
                );

                // The image's size once stored (optimising can make it narrower than the file the editor read)
                $size = @getimagesize($object_file->filepath);

                if ($size) {
                    $response['width']  = (int) $size[0];
                    $response['height'] = (int) $size[1];
                }

                // The editors write these as the image's srcset, so every template offers small screens the copy made for them
                foreach ($sources as $source) {
                    $response['srcset'][] = array(
                        'location' => str_replace(array(JPATH_ROOT, '\\'), array('', '/'), $source['path']),
                        'width'    => $source['width'],
                    );
                }

                echo json_encode($response);

                return;
            }
        } else {
            // A file the input filter refused (an executable or a double extension, e.g. shell.php.jpg): name it as such
            $raw     = $this->input->files->get('Filedata', null, 'raw');
            $message = is_array($raw) && !empty($raw['name']) ? 'JLIB_MEDIA_ERROR_WARNFILETYPE' : 'COM_MEDIA_ERROR_BAD_REQUEST';

            $response = array(
                'status'  => '0',
                'error'   => JText::_($message),
                'message' => JText::_($message)
            );

            echo json_encode($response);

            return;
        }
    }

    /**
     * Crops an image of the Media Manager (its Crop button): posted are the image's folder and name, the area in the image's
     * own pixels (x, y, w, h) and mode "new" (a file next to it, named with its size: photo_1920x823.jpg) or "replace" (the
     * image itself, which needs the right to delete too).
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function crop()
    {
        $user    = JFactory::getUser();
        $replace = $this->input->post->getCmd('mode') === 'replace';
        $error   = function ($key) {
            echo json_encode(array('status' => '0', 'message' => JText::_($key), 'error' => JText::_($key)));
        };

        if (!JSession::checkToken('post')) {
            $error('JINVALID_TOKEN');

            return;
        }

        if (!$user->authorise('core.create', 'com_media') || ($replace && !$user->authorise('core.delete', 'com_media'))) {
            $error('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN');

            return;
        }

        $folder = (string) $this->input->post->get('folder', '', 'path');
        $name   = (string) $this->input->post->get('file', '', 'string');
        $base   = realpath(COM_MEDIA_BASE);
        $path   = realpath(JPath::clean(COM_MEDIA_BASE . '/' . ($folder !== '' ? $folder . '/' : '') . $name));

        // A file of the media folder, named as the list names it (no folder in the name, not hidden: see isHidden())
        if ($name === '' || $name !== basename($name) || !$base || !$path || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)
            || $this->isHidden($name, $path)) {
            $error('COM_MEDIA_ERROR_UNABLE_TO_CROP');

            return;
        }

        $mediaHelper = new JHelperMedia();

        if (!$mediaHelper->canCrop($path)) {
            $error('COM_MEDIA_ERROR_CROP_TYPE');

            return;
        }

        $params = JComponentHelper::getParams('com_media');
        $x      = $this->input->post->getInt('x', -1);
        $y      = $this->input->post->getInt('y', -1);
        $width  = $this->input->post->getInt('w', 0);
        $height = $this->input->post->getInt('h', 0);
        $target = $path;

        if (!$replace) {
            $target = $mediaHelper->getUniqueFilePath(
                dirname($path) . DIRECTORY_SEPARATOR . pathinfo($path, PATHINFO_FILENAME) . '_' . $width . 'x' . $height . '.' . pathinfo($path, PATHINFO_EXTENSION)
            );
        }

        $mediaHelper->raiseMemoryLimit($params);

        // Plugins may refuse it, as an upload
        JPluginHelper::importPlugin('content');
        $dispatcher = JEventDispatcher::getInstance();
        $object     = new JObject(array('name' => basename($target), 'filepath' => $target, 'type' => 'image'));
        $result     = $dispatcher->trigger('onContentBeforeSave', array('com_media.file', &$object, !$replace));

        if (in_array(false, $result, true)) {
            $errors  = $object->getErrors();
            $message = JText::plural('COM_MEDIA_ERROR_BEFORE_SAVE', count($errors), implode('<br />', $errors));

            echo json_encode(array('status' => '0', 'message' => $message, 'error' => $message));

            return;
        }

        if (!$mediaHelper->cropImage($path, $x, $y, $width, $height, $target, $params->get('optimise_quality', 90))) {
            $error('COM_MEDIA_ERROR_UNABLE_TO_CROP');

            return;
        }

        $dispatcher->trigger('onContentAfterSave', array('com_media.file', &$object, !$replace));

        $relative = ltrim(str_replace(array($base, '\\'), array('', '/'), $target), '/');
        $message  = JText::sprintf($replace ? 'COM_MEDIA_CROP_REPLACED' : 'COM_MEDIA_CROP_SAVED', $relative);

        echo json_encode(array(
            'status'  => '1',
            'message' => $message,
            'path'    => $relative,
            'width'   => $width,
            'height'  => $height,
        ));
    }

    /**
     * Renames a file or a folder of the Media Manager's list (the pencil next to its name): posted are the list's folder,
     * the item's name, its type (file or folder) and the new name, made safe by MediaHelper::safeFileName() (a file keeps its
     * extension). Never over another file or folder; links to it in content aren't changed.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function rename()
    {
        $user  = JFactory::getUser();
        $error = function ($key, $name = '') {
            $message = $name !== '' ? JText::sprintf($key, $name) : str_replace(' %s', '', JText::_($key));

            echo json_encode(array('status' => '0', 'message' => $message, 'error' => $message));
        };

        if (!JSession::checkToken('post')) {
            $error('JINVALID_TOKEN');

            return;
        }

        if (!$user->authorise('core.create', 'com_media') || !$user->authorise('core.delete', 'com_media')) {
            $error('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN');

            return;
        }

        $isFolder = $this->input->post->getCmd('type') === 'folder';
        $folder   = (string) $this->input->post->get('folder', '', 'path');
        $name     = (string) $this->input->post->get('name', '', 'string');
        $base     = realpath(COM_MEDIA_BASE);
        $path     = realpath(JPath::clean(COM_MEDIA_BASE . '/' . ($folder !== '' ? $folder . '/' : '') . $name));

        // An item of the media folder (never the folder itself), named as the list names it, not hidden (see isHidden())
        if ($name === '' || $name !== basename($name) || !$base || !$path || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0
            || ($isFolder ? !is_dir($path) : !is_file($path)) || $this->isHidden($name, $path)) {
            $error('COM_MEDIA_ERROR_UNABLE_TO_RENAME', $name);

            return;
        }

        // Cleaned as uploads' names are; a file keeps its extension
        $mediaHelper = new JHelperMedia();
        $extension   = $isFolder ? '' : pathinfo($path, PATHINFO_EXTENSION);
        $wanted      = $mediaHelper->safeFileName((string) $this->input->post->get('newname', '', 'string'));
        $newName   = $wanted . ($extension !== '' ? '.' . $extension : '');

        if (!preg_match('/[a-zA-Z0-9]/', $wanted)) {
            $error('COM_MEDIA_ERROR_RENAME_NAME');

            return;
        }

        if ($newName === basename($path)) {
            echo json_encode(array('status' => '1', 'message' => '', 'name' => $newName));

            return;
        }

        $target = dirname($path) . DIRECTORY_SEPARATOR . $newName;

        // Another item of that name (also one differing in case only, on file systems which don't tell them apart)
        if (file_exists($target) && realpath($target) !== $path) {
            $error('COM_MEDIA_ERROR_RENAME_EXISTS', $newName);

            return;
        }

        if (!@rename($path, $target)) {
            $error('COM_MEDIA_ERROR_UNABLE_TO_RENAME', basename($path));

            return;
        }

        $message = JText::sprintf('COM_MEDIA_RENAMED', basename($path), $newName);

        echo json_encode(array('status' => '1', 'message' => $message, 'name' => $newName));
    }

    /**
     * Whether an item is hidden: its name, or the name of what it leads to (a link), starts with a dot. The list never shows
     * those, and they include the media folder's protection (images/.htaccess, which stops scripts running there): renaming
     * one turned that off.
     *
     * @param   string  $name  The name asked for
     * @param   string  $path  Its real path
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    protected function isHidden($name, $path)
    {
        return strpos($name, '.') === 0 || strpos(basename($path), '.') === 0;
    }
}
