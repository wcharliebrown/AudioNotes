# AudioNotes

A lightweight PHP web application for listening to audio files and taking timestamped notes. Designed for reviewing recordings, audiobooks, or any chapter-based audio content.

## Features

- **HTML5 audio playback** with chapter selection via dropdown
- **Resume from last position** — progress is saved automatically every 10 seconds
- **Skip controls** — jump back or forward 10 seconds
- **Auto-advance** to the next chapter when the current one finishes
- **Timestamped notes** — each note is tagged with the chapter and playback position
- **Per-chapter note files** stored as plain text for easy access
- **Dynamic chapter discovery** — add `.wav` files to a book folder and they appear automatically

## Tech Stack

- **Backend:** PHP 7.0+
- **Frontend:** HTML5, Bootstrap 5.3, Vanilla JavaScript
- **Storage:** JSON (progress), plain text files (notes)
- No database required

## Requirements

- PHP 7.0 or higher
- Web server (Apache, Nginx, etc.) with PHP support
- Write permissions on the `books/` directory
- Modern browser with HTML5 audio support

## Installation

1. Clone or download this repository into your web server's document root (or a subdirectory):

   ```bash
   git clone https://github.com/YOUR_USERNAME/AudioNotes.git
   ```

2. Create the `books/` directory and add a book folder with your `.wav` files:

   ```
   books/
   └── MyBook/
       ├── chapter_01_complete.wav
       ├── chapter_02_complete.wav
       └── ...
   ```

3. Ensure the web server has write access to `books/`:

   ```bash
   chmod -R 775 books/
   ```

4. Open `AudioNotes.php` in your browser via your web server.

## File Naming Convention

Audio files must follow this pattern:

```
chapter_XX_complete.wav
```

Where `XX` is a zero-padded chapter number (e.g., `chapter_01_complete.wav`, `chapter_12_complete.wav`).

## Project Structure

```
AudioNotes/
├── AudioNotes.php        # Main application (UI + backend logic)
├── README.md
└── books/
    └── MyBook/
        ├── chapter_01_complete.wav
        ├── chapter_01.txt        # Notes for chapter 1 (auto-created)
        └── progress.json         # Saved playback position (auto-created)
```

## Data Formats

**`progress.json`** — tracks current book, chapter, and playback position:
```json
{
  "book": "MyBook",
  "chapter": "1",
  "position": "234.56"
}
```

**`chapter_XX.txt`** — plain text notes file per chapter:
```
Chapter: 1, Position: 234.567
Your note text here

Chapter: 1, Position: 410.123
Another note here
```

## Usage

1. Load the page — it resumes from your last saved position.
2. Use the chapter dropdown to switch chapters.
3. Press play and use the skip buttons (±10s) to navigate.
4. Type a note and click **Save Note** — the current playback time is captured automatically.
5. Notes appear on the page and are saved to a text file for that chapter.

## License

MIT
