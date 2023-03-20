# OnlineMapServer
OnlineMapServer
==============

Start setting
------

<br /> 

### 1.Copy a blank configuration file
sudo mkdir config<br /> 
sudo cp Server_config.php ./config/Server_config.php<br />

### 2.Write a configuration file you just copied
sudo vim ./config/Server_config.php<br />

### 3.Windows please Run start.bat, Linux please Run start.sh

### 4.If "... All Done" is displayed on the terminal, it means the online map service has been started

### common problem:
The client cannot connect to the server:
Please check whether the server address configured by the client is correct
2. Please check whether the service firewall configuration has opened the corresponding port for your service (the default is 9998)
3. Unable to create the database. Please check whether the public account and password, as well as the root password, are correct
