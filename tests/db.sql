DROP DATABASE IF EXISTS `testdb`;
CREATE DATABASE IF NOT EXISTS `testdb`;
USE `testdb`;

CREATE TABLE `test_query` (
  `id` int NOT NULL,
  `key` varchar(25) NOT NULL,
  `value` varchar(50) NOT NULL
);

CREATE TABLE `test_table` (
  `id` int NOT NULL,
  `name` varchar(25) NOT NULL COMMENT 'It contains the name',
  `date` date NOT NULL,
  `value` varchar(15) NOT NULL
);

INSERT INTO `test_table` (`id`, `name`, `date`, `value`) VALUES (1, 'John', '2022-01-01', 'Red');
INSERT INTO `test_table` (`id`, `name`, `date`, `value`) VALUES (2, 'John2', '2022-06-01', 'Yellow');

ALTER TABLE `test_query`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `test_table`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `test_query`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `test_table`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
