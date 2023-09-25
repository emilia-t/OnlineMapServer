# online-map-server

This is a backend PHP service used to support online map editors

This project is based on Workman(4.0.19) and PHP(7.3.31) and PHPMailer(6.5.0)

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

If using Docker startup, you need to refer to the content of the start-docker.txt file in the root directory

### common problem:<br />
1.The client cannot connect to the server:<br />
Please check whether the server address configured by the client is correct<br />
2. Please check whether the service firewall configuration has opened the corresponding port for your service <br />
3. Unable to create the database. Please check whether the public account and password, as well as the root password, are correct<br />
