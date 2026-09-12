# ACCESS4ALL

One website, four accessibility modes — Blind & low vision, Deaf & hard of hearing, Non-speaking / communication, and Dyslexia & reading support.

## Project structure

```
access4all/
├── index.html              ← the whole frontend (one page, four modes)
├── api/
│   ├── simplify.php        ← text simplifier backend (Dyslexia mode + Blind study assistant)
│   └── describe-image.php  ← image description backend (Blind mode camera scan)
├── .gitignore
└── README.md
```

## Run it locally with XAMPP

1. Copy the whole `access4all` folder into your XAMPP `htdocs` directory
   (Windows: `C:\xampp\htdocs\access4all`, Mac: `/Applications/XAMPP/htdocs/access4all`).
2. Open the XAMPP Control Panel and click **Start** next to **Apache**.
3. Visit **http://localhost/access4all/** in Chrome.

Camera and microphone features need `localhost` (not a raw IP) to work — XAMPP's default setup already gives you that.

## Tech stack

- **Frontend:** vanilla HTML/CSS/JS, no framework, no build tools.
- **Backend:** PHP (runs on XAMPP's Apache out of the box, no extra install).
- **Speech:** browser-native Web Speech API (`SpeechSynthesis` for text-to-speech, `SpeechRecognition` for speech-to-text). Best support in Chrome/Edge.
- **Camera:** `navigator.mediaDevices.getUserMedia`.
- **Alarm detection:** Web Audio API (`AnalyserNode`) — a simplified heuristic for a sustained loud tone, not a trained sound classifier.
- **Word definitions:** free public dictionaryapi.dev REST API, called directly from the browser.
- **Vibration alerts:** `navigator.vibrate`.

## What's real vs. what's a placeholder

Working right now, fully wired to the PHP backend:
- Text simplifier (Dyslexia mode + Blind study assistant) → `api/simplify.php`
- Camera capture → description → `api/describe-image.php` (currently returns a placeholder sentence)

Working right now, no backend needed:
- Text-to-speech on every mode
- Live speech-to-text captions (Deaf mode) with emergency-keyword detection + vibration
- Alarm/siren heuristic detector (Deaf mode)
- Quick-phrase board, sentence builder, two-way conversation capture (Communication mode)
- Focus reading (one sentence at a time) and word lookup (Dyslexia mode)
- Scripted demo emergency route (Blind mode)

To make it fully AI-powered, open `api/simplify.php` and `api/describe-image.php` — each has a commented, ready-to-uncomment example of calling the Anthropic API with your own key.

## Setting up real AI image description (Blind mode camera scan)

1. In `api/`, copy `config.sample.php` and rename the copy to `config.php`.
2. Go to [console.anthropic.com](https://console.anthropic.com/), sign up if needed, and create an API key (Settings → API Keys). New accounts get some free credit.
3. Open `api/config.php` and paste your key in place of `your-api-key-here`.
4. Reload the site — "Scan surroundings" in Blind mode now sends the captured photo to Claude's vision model and speaks back a real description.

`config.php` is already excluded by `.gitignore`, so your key is never pushed to GitHub. **Never share your API key or commit it to a public repo.**

## Pushing this to GitHub

See the step-by-step walkthrough in the chat. Never commit a real API key — put it in `api/config.php`, which `.gitignore` already excludes.
