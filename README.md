# Fubra Analytics

- Only works in whole days.

- Remember to configure per user api limit to be more than 1 request per second. e.g. 1000/sec

- When copying create syntaxes into `install/database_structure.sql`, remember to add `IF NOT EXISTS` clauses to all table creations and also remove any `AUTO_INCREMENT` values.