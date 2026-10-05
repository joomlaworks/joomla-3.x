# Dates of a sample data build: articles created (and last changed) when published; the info pages (their category's alias is
# the second argument), categories and tags before them. Usage: fix_dates.sh <site> <pages category alias>
HERE=$(dirname "$0"); B=$1; PAGES=$2; PHP=${PHP:-php}; OLD='2026-08-01 09:00:00'
$PHP $HERE/q.php $B \
 "UPDATE #__content SET publish_up = '$OLD' WHERE catid = (SELECT id FROM #__categories WHERE alias = '$PAGES' AND extension = 'com_content')" \
 "UPDATE #__content SET created = publish_up, modified = publish_up" \
 "UPDATE #__ucm_content SET core_publish_up = (SELECT publish_up FROM #__content WHERE #__content.id = #__ucm_content.core_content_item_id) WHERE core_type_alias = 'com_content.article'" \
 "UPDATE #__ucm_content SET core_created_time = core_publish_up, core_modified_time = core_publish_up WHERE core_type_alias = 'com_content.article'" \
 "UPDATE #__contentitem_tag_map SET tag_date = (SELECT publish_up FROM #__content WHERE #__content.id = #__contentitem_tag_map.content_item_id) WHERE type_alias = 'com_content.article'" \
 "UPDATE #__categories SET created_time = '$OLD', modified_time = '$OLD' WHERE created_time != '0000-00-00 00:00:00'" \
 "UPDATE #__tags SET created_time = '$OLD', modified_time = '$OLD' WHERE created_time != '0000-00-00 00:00:00'" \
 "UPDATE #__tags SET publish_up = '$OLD' WHERE publish_up != '0000-00-00 00:00:00'" \
 "UPDATE #__tags SET publish_down = '0000-00-00 00:00:00', checked_out_time = '0000-00-00 00:00:00' WHERE id > 1" \
 "SELECT MAX(publish_up), MAX(created), MAX(modified) FROM #__content" "SELECT MAX(core_created_time), MAX(core_modified_time), MAX(core_publish_up) FROM #__ucm_content" "SELECT MAX(tag_date) FROM #__contentitem_tag_map" "SELECT MAX(created_time), MAX(modified_time) FROM #__categories" "SELECT MAX(created_time), MAX(modified_time), MAX(publish_up) FROM #__tags"
