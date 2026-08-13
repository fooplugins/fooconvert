import { access } from "node:fs/promises";

try {
    await access( new URL( "../pro/start.php", import.meta.url ) );
} catch {
    throw new Error(
        "The full build requires the private pro submodule. Initialize it or use the explicit free-only build/package commands."
    );
}