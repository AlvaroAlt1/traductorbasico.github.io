<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CONEXION A LA BD
    $conexion = new mysqli("localhost", "root", "12345678", "traductor");

    // VERIFICAR CONEXIÓN A LA BD
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // OBTENER EL TEXTO DE ENTRADA
    $textoentrada = strtolower(trim($_POST['inputText']));
    $palabras = explode(" ", $textoentrada);
    $palabrastraducidas = [];
    $palabracorrecta = [];

    // FUNCIÓN PARA ENCONTRAR PALABRAS SIMILARES
    function encontrarMasParecida($palabra, $conexion) {
        $sql = "SELECT espanol FROM diccionario";
        $resultado = $conexion->query($sql);

        if ($resultado->num_rows > 0) {
            $minDistancia = PHP_INT_MAX;
            $mejorCoincidencia = $palabra;

            while ($row = $resultado->fetch_assoc()) {
                $palabraBase = $row['espanol'];
                $distancia = levenshtein($palabra, $palabraBase);

                if ($distancia < $minDistancia) {
                    $minDistancia = $distancia;
                    $mejorCoincidencia = $palabraBase;
                }
            }

            return $mejorCoincidencia;
        }

        return $palabra; // Si no encuentra una palabra similar, devuelve la misma
    }

    foreach ($palabras as $palabra) {
        // BUSCAR PALABRA MÁS CERCANA EN ESPAÑOL
        $palabraCercana = encontrarMasParecida($palabra, $conexion);

        // AGREGAR A LA LISTA DE PALABRAS CORREGIDAS
        $palabracorrecta[] = $palabraCercana;

        // OBTENER SU TRADUCCIÓN
        $sql = "SELECT ingles FROM diccionario WHERE espanol = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $palabraCercana);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $row = $resultado->fetch_assoc();
            $palabrastraducidas[] = $row['ingles']; // TRADUCCIÓN
        } else {
            $palabrastraducidas[] = $palabra; // Si no encuentra la traducción, usa la misma palabra
        }
    }

    $oraciontraducida = implode(" ", $palabrastraducidas);
    $oracioncorregida = implode(" ", $palabracorrecta);

    // CERRAR CONEXIÓN A LA BD
    $conexion->close();

    // MOSTRAR RESULTADOS
    echo "<link rel='stylesheet' href='estilo.css'>";
    echo "<div class='container'>";
    echo "<h3>TEXTO INGRESADO (ESPAÑOL):</h3>";
    echo "<p class='entrada'>$textoentrada</p>";

    // MOSTRAR FRASE CORREGIDA SI ES DIFERENTE
    if ($textoentrada !== $oracioncorregida) {
        echo "<h3>TAL VEZ QUISISTE DECIR:</h3>";
        echo "<p class='correcion'>$oracioncorregida</p>";
    }

    echo "<h3>TRADUCCION (INGLÉS):</h3>";
    echo "<p class='traduccion' id='textotraducido'>$oraciontraducida</p>";

    echo "<a href='index.html' class='back-link'>Traducir otro texto</a>";
    echo "</div>";
}
?>







