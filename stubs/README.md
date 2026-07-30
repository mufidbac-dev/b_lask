# Stubs for scaffold generator

Each pattern has its own folder under `stubs/`. File names (without `.stub`) must match the keys
in `config/scaffold.php` → `patterns.{pattern}.files`.

To add a new pattern:
1. Create `stubs/{pattern}/` and add required `.stub` files.
2. Add entry in `config/scaffold.php` under `patterns` with `stub_path` and `files` mapping.
3. Update `.env` SCAFFOLD_PATTERN to switch project-wide.
