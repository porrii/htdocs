<?php
// Configura tu correo
$to = "info@inmavi.es";
$subject = "Nuevo mensaje desde el sitio web";

// Escapar entradas para mayor seguridad
$name = htmlspecialchars($_POST['name']);
$email = htmlspecialchars($_POST['email']);
$phone = htmlspecialchars($_POST['phone']);
$subjectField = htmlspecialchars($_POST['subject']);
$message = htmlspecialchars($_POST['message']);

// Mensaje completo
$body = "Nombre: $name\n";
$body .= "Email: $email\n";
$body .= "Teléfono: $phone\n";
$body .= "Asunto: $subjectField\n";
$body .= "Mensaje:\n$message";

// Encabezados
$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Enviar el email
if (mail($to, $subject, $body, $headers)) {
    http_response_code(200);
    echo "Mensaje enviado con éxito.";
} else {
    http_response_code(500);
    echo "Error al enviar el mensaje.";
}
?>
