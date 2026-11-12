<?php
session_start();
include '../db/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$id        = $_GET['id'];
$doctor_id = $_SESSION['user_id'];

mysqli_query($conn,
    "UPDATE appointments SET status='completed' 
     WHERE id='$id' AND doctor_id='$doctor_id'");

echo "<script>alert('Appointment Marked as Completed!');
      window.location='my_appointments.php';</script>";
?>