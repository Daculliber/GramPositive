<!DOCTYPE html>
<!--This is a demo implementation of the program-->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Model Tester</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f9f9f9; }
        .container { max-width: 600px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 100px; margin-bottom: 15px; padding: 10px; box-sizing: border-box; }
        input[type="submit"] { background: #007BFF; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background: #0056b3; }
        .result { margin-top: 20px; padding: 15px; border-left: 5px solid #28a745; background: #e9f7ef; }
    </style>
</head>
<body>

<div class="container">
    <h2>Text Classification Model Test</h2>
    
    <form method="post" action="">
        <label for="text_input">Enter text to classify:</label>
        <textarea name="text_input" id="text_input" required><?php echo isset($_POST['text_input']) ? htmlspecialchars($_POST['text_input']) : ''; ?></textarea>
        <input type="submit" value="Analyze Text">
    </form>

    <?php
    require_once 'GramPositive.php';
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['text_input'])) {
        
        $text_to_test = $_POST['text_input'];
        // Execute prediction using the pre-trained JSON model with the word matching function disabled
        $prediction = classify("model.json", $text_to_test, false);
        echo "<div class='result'>";
        echo "<strong>Classification Result:</strong> " . htmlspecialchars($prediction);
        echo "</div>";
    }
    ?>
</div>
</body>
</html>
