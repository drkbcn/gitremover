<?php

/**
 * Easy Delete User Access from GitHub
 * @version 1.0.0
 * @license MIT
 * @author Manel Alonso @drkbcn
 * @link https://github.com/drkbcn/gitremover
 */

use Dariuszp\CliProgressBar;

require_once __DIR__ . '/vendor/autoload.php';

showBanner();

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$githubToken = $_ENV['GITHUB_TOKEN'];

// Check if token is provided, if not, show help
if (!$githubToken) {
    echo 'Please provide a GitHub token in the .env file.' . PHP_EOL;
    exit;
}

// Check if username is provided, if not, show help
if (!isset($argv[1])) {
    echo 'Usage: php index.php <username>' . PHP_EOL;
    exit;
}

// Get the username from the command line
$usernameToRemove = $argv[1];

// Print message
echo 'Getting all repositories from GitHub...' . PHP_EOL;
$userRepos = getUserRepos($githubToken);

echo 'Total repositories: ' . count($userRepos) . PHP_EOL;

$bar = new CliProgressBar(
    count($userRepos),
    0,
    'Filtering repositories where the user ' . $usernameToRemove . ' has access...'
);

// Filter only the repositories where the user has access
$bar->display();
$repos = filterUserRepos($githubToken, $userRepos, $usernameToRemove, $bar);
$bar->end();

if (count($repos) === 0) {
    echo 'The user ' . $usernameToRemove . ' does not have access to any repository.' . PHP_EOL;
    exit;
}

echo 'Total repositories where the user ' . $usernameToRemove . ' has access: ' . count($repos) . PHP_EOL;

// Print the table with all the repositories
drawTable(['Name', 'Description', 'Private'], array_map(function ($repo) {
    return [
        $repo['name'],
        $repo['description'],
        $repo['private'] ? 'Yes' : 'No',
    ];
}, $repos));

// Deleting user access from all repositories
echo 'Deleting user access from all repositories...' . PHP_EOL;
removeUserAccess($repos, $githubToken, $usernameToRemove);

echo 'Done!' . PHP_EOL;

/**
 * Get all user repositories
 * @param string $token
 * @return array
 */
function getUserRepos($token)
{
    $repos = [];
    $page = 1;

    do {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.github.com/user/repos?page=$page&per_page=100",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: token $token", "User-Agent: PHP Script"],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode != 200) {
            break;
        }

        $pageRepos = json_decode($response, true);
        $repos = array_merge($repos, $pageRepos);
        $page++;
    } while (!empty($pageRepos));

    return $repos;
}

/**
 * Filter the repositories where the user has access
 * @param string $token
 * @param array $repos
 * @param string $username
 * @param CliProgressBar $bar
 * @return array
 */
function filterUserRepos($token, $repos, $username, &$bar)
{
    $filteredRepos = [];
    foreach ($repos as $repo) {
        $bar->progress();
        $repoName = $repo['name'];
        $owner = $repo['owner']['login'];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.github.com/repos/$owner/$repoName/collaborators/$username",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: token $token", "User-Agent: GitRemover"],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode === 204) {
            $filteredRepos[] = $repo;
        }
    }

    return $filteredRepos;
}

/**
 * Remove user access from all repositories
 * @param array $repos
 * @param string $token
 * @param string $username
 * @return void
 */
function removeUserAccess($repos, $token, $username)
{
    foreach ($repos as $repo) {
        $repoName = $repo['name'];
        $owner = $repo['owner']['login'];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.github.com/repos/$owner/$repoName/collaborators/$username",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: token $token", "User-Agent: GitRemover"],
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        ]);

        curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode === 204) {
            $repositories[] = $repoName;
        }
    }
}

/**
 * Draw a table for the console
 * @param array $headers
 * @param array $rows
 * @return void
 */
function drawTable(array $headers, array $rows)
{
    $widths = array_map('mb_strlen', $headers);
    foreach ($rows as $row) {
        foreach ($headers as $key => $header) {
            $value = (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) ? $row[$key] : '-';
            $widths[$key] = max($widths[$key], mb_strlen($value));
        }
    }

    $line = '+';
    foreach ($widths as $width) {
        $line .= str_repeat('-', $width + 2) . '+';
    }

    echo $line . "\n";
    echo "\033[47;30m| " . implode(' | ', array_map(function ($header, $width) {
        return mb_str_pad($header, $width);
    }, $headers, $widths)) . " |\033[0m\n";
    echo $line . "\n";

    foreach ($rows as $row) {
        $formattedRow = array_map(function ($key) use ($row, $widths) {
            $value = (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) ? $row[$key] : '-';
            return mb_str_pad($value, $widths[$key]);
        }, array_keys($headers));

        echo "| " . implode(' | ', $formattedRow) . " |\n";
    }

    echo $line . "\n";
}

/**
 * Show the banner
 * @return void
 */
function showBanner()
{
    echo "
     _______ __  ____  Easy Delete User Access from GitHub
    / ____(_) /_/ __ \___  ____ ___  ____ _   _____  _____
   / / __/ / __/ /_/ / _ \/ __ `__ \/ __ \ | / / _ \/ ___/
  / /_/ / / /_/ _, _/  __/ / / / / / /_/ / |/ /  __/ /    
  \____/_/\__/_/ |_|\___/_/ /_/ /_/\____/|___/\___/_/     
                                                              
    " . PHP_EOL;
}
