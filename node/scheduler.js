const { exec } = require('child_process');
const path = require('path');

// ANSI color codes for better output
const colors = {
    reset: '\x1b[0m',
    green: '\x1b[32m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    blue: '\x1b[36m',
    gray: '\x1b[90m'
};

// Path to Laravel artisan (parent directory)
const artisanPath = path.join(__dirname, '..', 'artisan');

// Function to get current timestamp
function timestamp() {
    return new Date().toISOString().replace('T', ' ').substring(0, 19);
}

// Function to run Laravel schedule command
function runLaravelSchedule() {
    const cmd = `php "${artisanPath}" schedule:run`;

    console.log(`${colors.blue}[${timestamp()}]${colors.reset} Running scheduler...`);

    exec(cmd, (error, stdout, stderr) => {
        if (error) {
            console.error(`${colors.red}[${timestamp()}] Error:${colors.reset} ${error.message}`);
            return;
        }

        if (stderr) {
            console.error(`${colors.red}[${timestamp()}] Stderr:${colors.reset} ${stderr}`);
            return;
        }

        // Show output if there are scheduled tasks running
        if (stdout && stdout.trim() !== 'No scheduled commands are ready to run.') {
            console.log(`${colors.green}[${timestamp()}] Output:${colors.reset}`);
            console.log(stdout);
        } else {
            console.log(`${colors.gray}[${timestamp()}] No tasks ready to run${colors.reset}`);
        }
    });
}

// Welcome message
console.log(`${colors.green}╔════════════════════════════════════════╗${colors.reset}`);
console.log(`${colors.green}║  Laravel Scheduler (Node.js Runner)   ║${colors.reset}`);
console.log(`${colors.green}╚════════════════════════════════════════╝${colors.reset}`);
console.log(`${colors.yellow}Started at: ${timestamp()}${colors.reset}`);
console.log(`${colors.blue}Running schedule:run every 60 seconds...${colors.reset}\n`);

// Run immediately on startup
runLaravelSchedule();

// Run every minute (60000 milliseconds)
const intervalId = setInterval(runLaravelSchedule, 60000);

// Handle graceful shutdown
process.on('SIGINT', () => {
    console.log(`\n${colors.yellow}[${timestamp()}] Shutting down scheduler...${colors.reset}`);
    clearInterval(intervalId);
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log(`\n${colors.yellow}[${timestamp()}] Shutting down scheduler...${colors.reset}`);
    clearInterval(intervalId);
    process.exit(0);
});
