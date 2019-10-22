<?php 
//Create a database connection right here.Provide your database credentials
$con=mysqli_connect('servername','username','password','database');

if (!$con)
  {
  die("Connection error: " . mysqli_connect_errno());
  }

 ?>