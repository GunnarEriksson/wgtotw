--
-- Create User Table
--
CREATE TABLE Lf_User
(
  id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  acronym CHAR(20) UNIQUE NOT NULL,
  firstName VARCHAR(80),
  lastName VARCHAR(80),
  town VARCHAR(80),
  email VARCHAR(80),
  gravatar VARCHAR(255),
  password VARCHAR(255),
  activityScore INT,
  numVotes INT,
  created DATETIME
) ENGINE INNODB CHARACTER SET utf8;


INSERT INTO Lf_User (acronym, firstName, lastName, town, email, gravatar, password, activityScore, numVotes, created) VALUES
  ('admin', 'Administrator', 'Administrator', 'Staden', 'wgtotw@mail.se', 'http://www.gravatar.com/avatar/4af1d7ebcf0b456d6b4e85ae64523539.jpg', '$2y$10$pbDZHhkpHhlHdzfrfBfleOSZAPsbhSQGRtRpZkmyqeke8iiT5Wa2K', 0, 0, NOW())
;



--
-- Create Question Table
--
CREATE TABLE Lf_Question
(
  id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  title VARCHAR(80),
  content VARCHAR(255),
  score INT,
  answers INT,
  created DATETIME
) ENGINE INNODB CHARACTER SET utf8;




--
-- Create Tag Table
--
CREATE TABLE Lf_Tag
(
  id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  label CHAR(20) NOT NULL,
  description VARCHAR(255),
  numQuestions INT
) ENGINE INNODB CHARACTER SET utf8;

INSERT INTO Lf_Tag (label, description, numQuestions) VALUES
  ('Landskap', 'En tag för landskap där vi ser horisonten som t ex öppna landskap eller bilder från hav och sjö.', 0),
  ('Intima-landskap', 'En tag för landskap där vi inte har någon horisont som t ex skogslandskap eller närbilder av det lilla landskapet.', 0),
  ('Platser', 'En tag för platser runt om vår värld, t ex en plats som man vill åka till eller där man redan har varit.', 0),
  ('Fototeknik', 'En tag för fototeknik där vi lär oss av varandra för att bli en bättre landskapsfotograf.', 0),
  ('Utrustning', 'En tag för utrustning där vi kan diskutera allt som rör vår utrustning som vi använder oss av när vi fotograferar.', 0),
  ('Kamera', 'En tag för kamera där vi kan diskutera allt som rör kameror.', 0),
  ('Objektiv', 'En tag för objektiv där vi kan diskutera allt som rör objektiv till våra kameror.', 0),
  ('Kläder', 'En tag för kläder där vi kan diskutera allt som rör våra kläder som vi använder oss av när vi är ute och fotograferar.', 0),
  ('Övrigt', 'En tag för övriga saker som vi kan använda oss av om vi vill disktuera något som inte passar in på de övriga taggarna.', 0)
;


--
-- Create Answer Table
--
CREATE TABLE Lf_Answer
(
  id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  content VARCHAR(255),
  score INT NOT NULL,
  accepted TINYINT NOT NULL,
  created DATETIME
) ENGINE INNODB CHARACTER SET utf8;


--
-- Create Comment Table
--
CREATE TABLE Lf_Comment
(
  id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  content VARCHAR(255),
  score INT,
  created DATETIME
) ENGINE INNODB CHARACTER SET utf8;


--
-- Create Question Vote table
--
CREATE TABLE Lf_QuestionVote
(
  id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  idQuestion INT NOT NULL,
  idUser INT NOT NULL
) ENGINE INNODB CHARACTER SET utf8;

--
-- Create Answer Vote table
--
CREATE TABLE Lf_AnswerVote
(
  id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  idAnswer INT NOT NULL,
  idUser INT NOT NULL
) ENGINE INNODB CHARACTER SET utf8;

--
-- Create Comment Vote table
--
CREATE TABLE Lf_CommentVote
(
  id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  idComment INT NOT NULL,
  idUser INT NOT NULL
) ENGINE INNODB CHARACTER SET utf8;

--
-- Create User Question connection table.
--
CREATE TABLE Lf_User2Question
(
  idUser INT NOT NULL,
  idQuestion INT NOT NULL,

  FOREIGN KEY (idUser) REFERENCES Lf_User (id),
  FOREIGN KEY (idQuestion) REFERENCES Lf_Question (id),

  PRIMARY KEY (idUser, idQuestion)
) ENGINE INNODB;


--
-- Create Question Tag connection table.
--
CREATE TABLE Lf_Question2Tag
(
  idQuestion INT NOT NULL,
  idTag INT NOT NULL,

  FOREIGN KEY (idQuestion) REFERENCES Lf_Question (id),
  FOREIGN KEY (idTag) REFERENCES Lf_Tag (id),

  PRIMARY KEY (idQuestion, idTag)
) ENGINE INNODB;


--
-- Create Question Answer connection table.
--
CREATE TABLE Lf_Question2Answer
(
  idQuestion INT NOT NULL,
  idAnswer INT NOT NULL,

  FOREIGN KEY (idQuestion) REFERENCES Lf_Question (id),
  FOREIGN KEY (idAnswer) REFERENCES Lf_Answer (id),

  PRIMARY KEY (idQuestion, idAnswer)
) ENGINE INNODB;


--
-- Create User Answer connection table.
--
CREATE TABLE Lf_User2Answer
(
  idUser INT NOT NULL,
  idAnswer INT NOT NULL,

  FOREIGN KEY (idUser) REFERENCES Lf_User (id),
  FOREIGN KEY (idAnswer) REFERENCES Lf_Answer (id),

  PRIMARY KEY (idUser, idAnswer)
) ENGINE INNODB;

--
-- Create User Answer connection table.
--
CREATE TABLE Lf_User2Comment
(
  idUser INT NOT NULL,
  idComment INT NOT NULL,

  FOREIGN KEY (idUser) REFERENCES Lf_User (id),
  FOREIGN KEY (idComment) REFERENCES Lf_Comment (id),

  PRIMARY KEY (idUser, idComment)
) ENGINE INNODB;

--
-- Create Question Comment connection table.
--
CREATE TABLE Lf_Question2Comment
(
  idQuestion INT NOT NULL,
  idComment INT NOT NULL,

  FOREIGN KEY (idQuestion) REFERENCES Lf_Question (id),
  FOREIGN KEY (idComment) REFERENCES Lf_Comment (id),

  PRIMARY KEY (idQuestion, idComment)
) ENGINE INNODB;


--
-- Create Answer Comment connection table.
--
CREATE TABLE Lf_Answer2Comment
(
  idAnswer INT NOT NULL,
  idComment INT NOT NULL,

  FOREIGN KEY (idAnswer) REFERENCES Lf_Answer (id),
  FOREIGN KEY (idComment) REFERENCES Lf_Comment (id),

  PRIMARY KEY (idAnswer, idComment)
) ENGINE INNODB;
