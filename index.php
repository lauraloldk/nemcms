<?php
session_start();
// store session data
$_SESSION['funktion']=ret-forside;
?>

<html>
denne funktion kommer snart!
<body>

<?php
//retrieve session data
echo "funktion=". $_SESSION['funktioner'];
?>

</body>
</html> 
<?php include 'header.php'; ?>
<div class="contentbox">velkommen til din side (ret teksten her til det ønskede)</div>
<div class="contentbox">siden åbner snart!</div>
<?php include 'footer.php'; ?>
