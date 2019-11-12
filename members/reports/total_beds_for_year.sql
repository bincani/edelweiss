SELECT
  SUM(`members`) as 'members',
  SUM(`juniors`) as 'junior members',
  SUM(`adult_guests`) as 'guests',
  SUM(`child_guests`) as 'junior guests'
FROM `edelweiss_days` WHERE year(`date`) = 2019;
