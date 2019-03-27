<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use DaveChild\TextStatistics\TextStatistics as TS;

$textStatistics = new TS;

function api_client() {

    $base_uri = 'https://en.wikipedia.org/w/api.php';

    $headers = [
        'User-Agent' => 'wrac',
        'Content-Type' => 'application/json',
    ];

    $client = new Client([
        'base_uri' => $base_uri,
        'headers' => $headers,
    ]);

    return $client;
}

$client = api_client();

# TODO: If I wanted to be fancy, I'd continue through all the categories, and investigate the best way to cache the responses.
# The acmin value here is a hack. Even with a value of one, I was finding categories with no category members on followup queries. I assume there are category members (like subcategories) that I'm not expecting.
# So, for now, just grab categories with lots of members.
# TODO: This should be in a try block with exception logging.
$response = $client->request('GET', '', [
    'query' => ['action' => 'query', 'format' => 'json', 'acmin' => '1000', 'list' => 'allcategories']
]);


$json = json_decode((string) $response->getBody(), true);


# TODO: I should probably be using a framework for this even though it's a single page app.
?>
<form action="http://localhost" method="post">
<select name="category">
<?php
foreach ( $json['query']['allcategories'] as $category ) {
    ?><option><?php print $category['*']; ?></option><?php
}
?>
</select>
<input type="submit" />
<?php


?>
</form>

<?php

# If we have a category from form post.
if (!empty($_POST['category'])) {

    $category = $_POST['category'];
    $cmtitle = 'Category:' . $category;
    echo '<h1>' . $cmtitle . '</h1>';

    $response = $client->request('GET', '', [
        'query' => ['action' => 'query', 'format' => 'json', 'cmtitle' => $cmtitle, 'cmlimit' => '50', 'list' => 'categorymembers']
    ]);

    $json = json_decode((string) $response->getBody(), true);

    # If we have results.
    if(!empty($json['query']['categorymembers'])) {

        # Store our results.
        $results = Array();

        # This is an old-fashioned imperative style for speed of writing. It would be better done as class methods or functions.
        foreach ( $json['query']['categorymembers'] as $categorymember ) {

            # TODO: This would probably be better done async.
            # I tried piping together the pageids into a single call, but I that only returned an extract for one page.
            $response = $client->request('GET', '', [
                'query' => ['action' => 'query', 'format' => 'json', 'prop' => 'extracts|info', 'inprop' => 'url', 'explaintext' => 'true', 'pageids' => $categorymember['pageid']]
            ]);

            $json = json_decode((string) $response->getBody(), true);

            # Push the parsed results into our array.
            foreach ( $json['query']['pages'] as $page ) {
                $readability = $textStatistics->fleschKincaidReadingEase($page['extract']);
                array_push($results, array('title' => $page['title'], 'fullurl' => $page['fullurl'], 'readability' => $readability, 'extract' => $page['extract']));
            }


            # Sort ascending by readability.
            uasort($results, function($a, $b) {
                return $a['readability'] <=> $b['readability'];
            });
        }

        # Display the results.
        print '<ul>';
        foreach ( $results as $result ) {
                ?>
                <li>
                    <p>
                        <a href="<?php print $result['fullurl']; ?>"><?php print $result['title']; ?></a>
                        <p>Readability score: <?php print $result['readability']; ?></p>
                    </p>
                </li>
                <?php
        }
        print '</ul>';
    # If we don't have results.
    } else {
    print '<p>No results found.</p>';
    }

}

?>
