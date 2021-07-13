UPDATE
	`posts` AS `target`,
	(SELECT * FROM `posts` WHERE boardid = 2 and id IN (
		SELECT max(id) AS id FROM `posts` WHERE IS_DELETED != 1 AND boardid = 2 AND parentid IN (
			SELECT id FROM `posts` WHERE IS_DELETED != 1 AND boardid = 2 AND parentid = 0 GROUP BY id ORDER BY id ASC
		) GROUP BY parentid
	) ORDER BY parentid ASC) AS `source`
SET
	`target`.`bumped` = `source`.`timestamp`
WHERE
	`target`.`parentid` = 0 AND `target`.`id` = `source`.`parentid` AND `target`.`boardid` = `source`.`boardid` AND `target`.`bumped` != `source`.`timestamp`
;