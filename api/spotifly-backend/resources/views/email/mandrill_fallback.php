<!DOCTYPE html>
<html>
<head>
    <title>Mandrill fallback</title>
</head>
<body>
    <!-- Debugging line to verify the content -->
    <p>
        <?php
        foreach ($data ?? [] as $key => $value) {
            // value may be an array, loop through it until is not an array and then echo it
            if (\is_array($value)) {
                foreach ($value as $k => $v) {
                    // if v is still an array then just print it
                    if (\is_array($v)) {
                        echo \htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . ': ' . \htmlspecialchars(\var_export($v, true), ENT_QUOTES, 'UTF-8') . '<br>';
                    } else {
                        echo \htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . ': ' . \htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '<br>';
                    }
                }
            } else {
                echo \htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . ': ' . \htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '<br>';
            }

            echo '<br>';
        }
        ?>
    </p>
</body>
</html>
