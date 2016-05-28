
-- FIXME: Does sync catch *all* records? Also Publications? Do we need this migration?
UPDATE tx_ncgovpermits_permits SET lastmodified = tstamp;