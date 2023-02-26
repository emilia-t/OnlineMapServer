# OnlineMapServer
OnlineMapServer
==============

Start setting
------

set your mysql config<br /> 

### 1.Create a file named "config" in the root directory 
sudo mkdir config<br /> 

### 2.Create the following php file
sudo touch Mysql_OZ4pTiFHZf.php<br /> 
sudo touch RSA_DersJx8t8F.php<br /> 
sudo touch SMTP_U4tqjLYxNw.php<br /> 

### 3.Configure MySQL account
sudo vim Mysql_OZ4pTiFHZf.php<br /> 

insert:<br /> 

$mysql_public_server_address="localhost";//Database address<br /> 
$mysql_public_user="map_edit";//Public map database account<br /> 
$mysql_public_password="password";//account password<br /> 
$mysql_public_db_name="map";//The name of the map database, If there is no map database, we will create it automatically<br /> 
$mysql_root_password="password";//The password of the root account will only be used when creating the database. You can change this value to an empty string after ensuring that the map database has no problems<br /> 

### 4.Configure RSA, This is used for encrypted communication
sudo vim RSA_DersJx8t8F.php<br /> 

insert:<br /> 

define("RSA_public","-----BEGIN PUBLIC KEY----------END PUBLIC KEY-----");//Type your public key here<br /> <br /> 
define("RSA_private","-----BEGIN PRIVATE KEY----------END PRIVATE KEY-----");//Type your private key here<br /> <br /> 

### 5.Configure SMTP, This is for user registration
sudo vim SMTP_U4tqjLYxNw.php<br /> 

insert:<br /> 

$licenseCodePOP3='';//Type the POP3 code of your email address<br /> 
$licenseCodeIMAP='';//Type the IMAP code of your email address<br /> 

### 6.Windows please Run start.bat, Linux please Run start.sh

### 7.If "... All Done" is displayed on the terminal, it means the online map service has been started

