
# GitRemover

## Overview
**GitRemover** is a PHP tool designed to simplify the process of removing a user from all GitHub repositories they have access to. This is particularly useful for administrators managing large teams or when a team member leaves an organization. The tool operates by utilizing a GitHub token for authentication and authorization.

## Prerequisites
- PHP 7.4 or higher
- Composer (for installing dependencies)

## Installation
To set up **GitRemover**, follow these steps:

1. Clone the repository:
   ```
   git clone https://github.com/your-username/gitremover.git
   ```
2. Navigate to the cloned repository:
   ```
   cd gitremover
   ```
3. Install dependencies using Composer:
   ```
   composer install
   ```

## Configuration
Before you can use **GitRemover**, you need to provide a GitHub token. Generate a token with sufficient permissions to remove users from repositories.

1. Create a `.env` file in the root directory of **GitRemover**.
2. Add the following line to the `.env` file:
   ```
   GITHUB_TOKEN=your_github_token_here
   ```

## Usage
To use **GitRemover**, run the script with the user's GitHub username as an argument:
   ```
   php index.php username-to-remove
   ```

## Contributing
Contributions to **GitRemover** are welcome! Please follow the standard GitHub flow: fork the repository, make your changes, and submit a pull request.

## License
**GitRemover** is released under the MIT License. See the `LICENSE` file for more details.
