-- Joomla 3.x UTD 3.18.0 migration — October 17, 2026
-- Smart Search: an index which serves its lookups of links by URL (url = ...). The stock index, on substr(url,0,76), never
-- did, so every item indexed, saved or removed scanned the whole links table. Converted sites had a plain one of that name.
-- Written so that Extensions: Database can check both statements (it skips those of fewer than six words).

DROP INDEX IF EXISTS "#__finder_links_idx_url" CASCADE;
CREATE INDEX "#__finder_links_idx_url_full" ON "#__finder_links" USING btree ("url");
