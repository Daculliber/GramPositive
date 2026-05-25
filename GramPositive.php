<?php
//version 1.0
function tokenize($text) {
    // Take text block, separate into sentences, remove punctuation, 
    // and then separate into words
    $text = strtolower($text); // Make text lowercase

    // Normalize punctuation for splitting and remove commas
    $text = str_replace(["!", "?"], ".", $text);
    $text = str_replace(",", "", $text);

    $textParts = explode(".", $text);
    $sentences = [];

    foreach ($textParts as $sent) {
        $st = explode(" ", $sent);
        
        // Remove empty strings from the array
        $st = array_filter($st, function($word) {
            return $word !== "";
        });

        // Reset array keys so it behaves like a clean Python list
        $st = array_values($st);

        if (!empty($st)) {
            $sentences[] = $st;
        }
    }

    return $sentences;} 



function process_text($json_file_path, $text, $word_match = false) {
    if (!file_exists($json_file_path)) { 
        return ["error" => "Model file missing"]; 
    }
    $json_string = file_get_contents($json_file_path);
    $model = json_decode($json_string, true);
    
    $word_index = $model["index"];
    $matrix_model = $model["matrix"];
    $default_label = $model["default"];
    $scores = [];
    
    $sentences = tokenize($text);
    $wordcount = 0;
    
    // Set your maximum allowed character edits (1 is best for typos/leet speak)
    $lev_threshold = 1; 
    
    foreach ($sentences as $sent) {
        $prec = "*43$#00"; // set precedent to *43$#00 if there is no precedent
        
        foreach ($sent as $word) {
            // If word not in word_index
            if (!isset($word_index[$word])) {
                if ($word_match === true) {
                    $closest_word = null;
                    $shortest_dist = $lev_threshold + 1;
                    
                    // Replaces Python's lst_about by checking the index keys natively
                    foreach ($word_index as $valid_word => $id) {
                        $dist = levenshtein($word, $valid_word);
                        
                        if ($dist < $shortest_dist) {
                            $closest_word = $valid_word;
                            $shortest_dist = $dist;
                        }
                        
                        // Early exit if we find a perfect 1-edit match to save CPU cycles
                        if ($shortest_dist === 1) {
                            break;
                        }
                    }
                    
                    // If a close match was found within our threshold, use it
                    if ($closest_word !== null && $shortest_dist <= $lev_threshold) {
                        $word = $closest_word;
                    }
                } else {
                    continue; // Equivalent to Python's 'pass' when word_match is False
                }
            }
            
            // If word in word_index
            if (isset($word_index[$word])) {
                $wordcount++; // count the words so that an average score can be calculated
                
                $value = $word_index[$word];
                $prec_value = isset($word_index[$prec]) ? $word_index[$prec] : "";
                $pattern = "{$prec_value}:{$value}";
                
                $prec = $word; // the prec only gets updated if the word exists
                
                // Get the pattern scores but only if the pattern exists in the model
                if (isset($matrix_model[$pattern])) {
                    $labels = $matrix_model[$pattern];
                } else {
                    // fallback for unknown patterns
                    $labels = [$default_label => 1];
                }
                
                // Update the scores with the current labels
                foreach ($labels as $label => $label_value) {
                    if (isset($scores[$label])) {
                        $scores[$label] += $label_value;
                    } else {
                        $scores[$label] = $label_value;
                    }
                }
            }
        }
    }
    
    // Calculate average to avoid getting higher scores for longer sentences
    if ($wordcount !== 0) {
        $avg_scores = [];
        foreach ($scores as $label => $score) {
            $avg_scores[$label] = $score / $wordcount;
        }
        $scores = $avg_scores;
    }
    
    return $scores;
}

function classify($json_file_path, $text, $word_match = false) {
    // 1. Get the scores from the processing function
    $results = process_text($json_file_path, $text, $word_match);
    
    $verdict = "none";
    $highest = 0;
    
    // 2. Loop through results to find the key with the highest score
    foreach ($results as $label => $score) {
        if ($score > $highest) {
            $highest = $score;
            $verdict = $label;
        }
    }
    
    return $verdict;
}
?>
<!-- ////////////////////////////////////   Demonstration   //////////////////////////////////////////////////// -->
<!DOCTYPE html>
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
    // --- 1. RUN THE MODEL IF FORM IS SUBMITTED ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['text_input'])) {
        
        $text_to_test = $_POST['text_input'];
        
        // run the classify function to return the result
        $prediction = classify("model.json", $text_to_test, false);
        
        // Display the result
        echo "<div class='result'>";
        echo "<strong>Classification Result:</strong> " . htmlspecialchars($prediction);
        echo "</div>";
    }
    ?>
</div>