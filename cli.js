#!/usr/bin/env node

const { program } = require("commander");
const { spawnSync } = require("child_process");
const fs = require("fs");
const path = require("path");

program
  .name("markitdown")
  .description("Convert files to Markdown using Microsoft's MarkItDown")
  .version("1.0.0")
  .argument("[file]", "File to convert (reads from stdin if omitted)")
  .option("-o, --output <file>", "Write output to a file instead of stdout")
  .option("--check", "Verify that markitdown (Python) is installed and exit")
  .parse(process.argv);

const opts = program.opts();
const [inputFile] = program.args;

if (opts.check) {
  const result = spawnSync("markitdown", ["--version"], { encoding: "utf8" });
  if (result.error) {
    console.error("markitdown is NOT installed. Run: pip install markitdown");
    process.exit(1);
  }
  console.log("markitdown is installed:", result.stdout.trim() || "(version unknown)");
  process.exit(0);
}

function ensureMarkitdownInstalled() {
  const check = spawnSync("markitdown", ["--version"], { encoding: "utf8" });
  if (check.error) {
    console.error(
      "Error: Python 'markitdown' is not installed or not on PATH.\n" +
      "Install it with: pip install markitdown"
    );
    process.exit(1);
  }
}

function convert(filePath) {
  ensureMarkitdownInstalled();

  const args = filePath ? [filePath] : [];
  const input = filePath ? undefined : process.stdin;

  const result = spawnSync("markitdown", args, {
    encoding: "utf8",
    stdio: [input || "inherit", "pipe", "pipe"],
  });

  if (result.error) {
    console.error("Failed to run markitdown:", result.error.message);
    process.exit(1);
  }

  if (result.status !== 0) {
    process.stderr.write(result.stderr || "markitdown exited with an error.\n");
    process.exit(result.status);
  }

  const markdown = result.stdout;

  if (opts.output) {
    const outPath = path.resolve(opts.output);
    fs.writeFileSync(outPath, markdown, "utf8");
    console.error(`Written to ${outPath}`);
  } else {
    process.stdout.write(markdown);
  }
}

if (inputFile) {
  const resolved = path.resolve(inputFile);
  if (!fs.existsSync(resolved)) {
    console.error(`File not found: ${resolved}`);
    process.exit(1);
  }
  convert(resolved);
} else {
  // No file argument — pass stdin through to markitdown
  convert(null);
}
