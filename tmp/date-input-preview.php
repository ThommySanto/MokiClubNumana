<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Date Input Preview</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #e0e5ec;
            font-family: Arial, Helvetica, sans-serif;
            color-scheme: light;
        }

        .date-field-shell {
            width: min(420px, 100%);
            display: grid;
            gap: 8px;
        }

        .date-field-label {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #3d4468;
            padding-left: 6px;
        }

        #data_nascita {
            width: 100%;
            min-height: 56px;
            border: 1px solid rgba(61, 68, 104, 0.18);
            outline: none;
            border-radius: 18px;
            padding: 18px 54px 18px 22px;
            background-color: #f7f9fc;
            box-shadow:
                0 10px 24px rgba(190, 195, 207, 0.45),
                inset 4px 4px 10px rgba(190, 195, 207, 0.55),
                inset -4px -4px 10px rgba(255, 255, 255, 0.95);
            font: inherit;
            font-size: 16px;
            color: #3d4468;
            -webkit-text-fill-color: #3d4468;
            caret-color: #3d4468;
            font-variant-numeric: tabular-nums;
            -webkit-appearance: auto;
            appearance: auto;
            background-clip: padding-box;
        }

        #data_nascita:focus {
            box-shadow:
                0 12px 28px rgba(190, 195, 207, 0.55),
                inset 3px 3px 8px rgba(190, 195, 207, 0.6),
                inset -3px -3px 8px rgba(255, 255, 255, 1);
            background-color: #ffffff;
            border-color: rgba(61, 68, 104, 0.28);
        }

        #data_nascita::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 1;
            width: 20px;
            height: 20px;
            margin-right: 2px;
            filter: invert(23%) sepia(16%) saturate(604%) hue-rotate(194deg) brightness(91%) contrast(89%);
        }

        #data_nascita::-webkit-datetime-edit,
        #data_nascita::-webkit-datetime-edit-text,
        #data_nascita::-webkit-datetime-edit-month-field,
        #data_nascita::-webkit-datetime-edit-day-field,
        #data_nascita::-webkit-datetime-edit-year-field {
            color: #3d4468;
        }

        #data_nascita::placeholder {
            color: rgba(61, 68, 104, 0.75);
            opacity: 1;
        }

        @media (hover: none), (pointer: coarse), (max-width: 768px) {
            body {
                padding: 14px;
            }

            .date-field-shell {
                width: 100%;
                gap: 6px;
            }

            .date-field-label {
                font-size: 11px;
                letter-spacing: 0.06em;
                padding-left: 6px;
            }

            #data_nascita {
                min-height: 50px;
                padding: 12px 14px;
                border-radius: 14px;
                background-color: #ffffff;
                box-shadow:
                    0 8px 18px rgba(190, 195, 207, 0.34),
                    inset 2px 2px 6px rgba(190, 195, 207, 0.35),
                    inset -2px -2px 6px rgba(255, 255, 255, 0.95);
                cursor: pointer;
            }

            #data_nascita::-webkit-calendar-picker-indicator {
                width: 22px;
                height: 22px;
            }
        }

    </style>
</head>
<body>
    <div class="date-field-shell">
        <label class="date-field-label" for="data_nascita">Data di nascita</label>
        <input id="data_nascita" name="data_nascita" type="date" inputmode="numeric" autocomplete="bday" placeholder="gg/mm/aaaa">
    </div>

    <script>
        (function () {
            document.getElementById('data_nascita');
        })();
    </script>
</body>
</html>
