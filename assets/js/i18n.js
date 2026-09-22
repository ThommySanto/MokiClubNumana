/**
 * Moki Club Numana — Selettore lingua per i moduli pubblici (Iscrizione / Rimessaggio)
 *
 * IMPORTANTE: questo script traduce SOLO i testi visibili (label, placeholder,
 * testo dei bottoni, testo delle <option>). Non tocca MAI gli attributi
 * "name" o "value" di input/select, quindi i dati inviati al server restano
 * sempre in italiano come si aspettano submit_iscrizione.php e
 * submit_rimessaggio.php. Nessuna modifica lato backend è necessaria.
 */
(function () {
    "use strict";

    var STORAGE_KEY = "moki_lang";
    var DEFAULT_LANG = "it";
    var SUPPORTED = ["it", "en", "fr", "es", "de"];

    var LANG_META = {
        it: { flag: "🇮🇹", label: "IT" },
        en: { flag: "🇬🇧", label: "EN" },
        fr: { flag: "🇫🇷", label: "FR" },
        es: { flag: "🇪🇸", label: "ES" },
        de: { flag: "🇩🇪", label: "DE" }
    };

    var translations = {
        it: {
            switcher_label: "Seleziona la lingua",
            nome_placeholder: "Nome",
            cognome_placeholder: "Cognome",
            luogo_nascita_placeholder: "Luogo di nascita",
            data_nascita_label: "Data di nascita",
            indirizzo_residenza_label: "Indirizzo di residenza",
            indirizzo_residenza_placeholder: "Indirizzo di residenza",
            indirizzo_label: "Indirizzo",
            indirizzo_placeholder: "Indirizzo",
            citta_placeholder: "Città",
            cap_placeholder: "CAP",
            telefono_label: "Telefono",
            telefono_placeholder: "333 0000000",
            email_label: "Email",
            email_placeholder: "Email",
            tipo_documento_placeholder: "Tipo Documento",
            doc_carta_identita: "Carta d'identità",
            doc_carta_identita_alt: "Carta Identità",
            doc_codice_fiscale: "Codice Fiscale",
            doc_patente: "Patente",
            doc_passaporto: "Passaporto",
            numero_documento_placeholder: "Numero documento",
            privacy_text: "Accetto le condizioni della privacy policy",
            privacy_hint: "(Leggi le privacy policy per abilitare)",
            privacy_link_text: "Leggi le privacy policy",
            firma_label: "Firma Digitale",
            firma_placeholder_text: "🖌️ Tocca qui per firmare",
            firma_modal_title: "Firma qui",
            firma_modal_subtitle: "Usa il dito o il mouse per firmare nello spazio bianco",
            btn_conferma: "Conferma",
            btn_cancella: "Cancella",
            btn_chiudi: "Chiudi",
            firma_caricata_label: "Firma Caricata",
            date_placeholder: "gg/mm/aaaa",
            iscrizione_title: "Modulo Iscrizione Moki Club Numana",
            categoria_placeholder: "Adulto o Kid",
            categoria_adulto: "Adulto",
            categoria_kid: "Kid",
            tipo_iscrizione_placeholder: "Tipo iscrizione",
            ti_iscrizione: "Iscrizione",
            ti_iscrizione_noleggio: "Iscrizione + Noleggio",
            ti_abbonamento: "Abbonamento",
            ti_lezione: "Lezione",
            ti_alba: "Alba",
            ti_notturna: "Notturna",
            newsletter_text: "Voglio ricevere comunicazioni via email",
            sa_nuotare_text: "Dichiaro di saper nuotare",
            invia_iscrizione_btn: "Invia iscrizione",
            rimessaggio_title: "Modulo Rimessaggio Moki Club Numana",
            dati_tavola_title: "Dati Tavola",
            tavola_marca_placeholder: "Tavola Marca",
            tavola_modello_placeholder: "Tavola Modello",
            tavola_anno_placeholder: "Anno Tavola",
            sacca_placeholder: "Sacca",
            sacca_si: "Sì",
            sacca_no: "No",
            pagaia_placeholder: "Pagaia (Marca e Modello)",
            altro_placeholder: "Altro materiale lasciato",
            dati_documento_title: "Dati Documento",
            adulto_kid_placeholder: "Adulto o Kid",
            tipo_rimessaggio_placeholder: "Tipo Rimessaggio",
            tr_settimanale: "Settimanale (90€ + 10€ tesseramento)",
            tr_mensile: "Mensile (180€ + 10€ tesseramento)",
            tr_annuale: "Annuale (450€)",
            acconto_placeholder: "Acconto €",
            data_versamento_label: "Data versamento acconto",
            ricevuta_label: "Ricevuta di pagamento",
            ricevuta_help: "Carica la ricevuta di pagamento in formato PDF o immagine.",
            salva_rimessaggio_btn: "Salva Rimessaggio",
            privacy_alert: "Devi accettare le condizioni della privacy policy per inviare il modulo.",
            date_format_alert: "Inserisci la data nel formato gg/mm/aaaa."
        },
        en: {
            switcher_label: "Select language",
            nome_placeholder: "First name",
            cognome_placeholder: "Last name",
            luogo_nascita_placeholder: "Place of birth",
            data_nascita_label: "Date of birth",
            indirizzo_residenza_label: "Home address",
            indirizzo_residenza_placeholder: "Home address",
            indirizzo_label: "Address",
            indirizzo_placeholder: "Address",
            citta_placeholder: "City",
            cap_placeholder: "ZIP / Postal code",
            telefono_label: "Phone",
            telefono_placeholder: "333 0000000",
            email_label: "Email",
            email_placeholder: "Email",
            tipo_documento_placeholder: "Document type",
            doc_carta_identita: "ID Card",
            doc_carta_identita_alt: "ID Card",
            doc_codice_fiscale: "Tax Code",
            doc_patente: "Driving Licence",
            doc_passaporto: "Passport",
            numero_documento_placeholder: "Document number",
            privacy_text: "I accept the privacy policy terms",
            privacy_hint: "(Read the privacy policy to enable)",
            privacy_link_text: "Read the privacy policy",
            firma_label: "Digital Signature",
            firma_placeholder_text: "🖌️ Tap here to sign",
            firma_modal_title: "Sign here",
            firma_modal_subtitle: "Use your finger or mouse to sign in the white area",
            btn_conferma: "Confirm",
            btn_cancella: "Clear",
            btn_chiudi: "Close",
            firma_caricata_label: "Uploaded signature",
            date_placeholder: "dd/mm/yyyy",
            iscrizione_title: "Moki Club Numana Membership Form",
            categoria_placeholder: "Adult or Kid",
            categoria_adulto: "Adult",
            categoria_kid: "Kid",
            tipo_iscrizione_placeholder: "Membership type",
            ti_iscrizione: "Membership",
            ti_iscrizione_noleggio: "Membership + Rental",
            ti_abbonamento: "Season pass",
            ti_lezione: "Lesson",
            ti_alba: "Sunrise session",
            ti_notturna: "Night session",
            newsletter_text: "I want to receive email communications",
            sa_nuotare_text: "I declare that I can swim",
            invia_iscrizione_btn: "Submit membership",
            rimessaggio_title: "Moki Club Numana Board Storage Form",
            dati_tavola_title: "Board Details",
            tavola_marca_placeholder: "Board brand",
            tavola_modello_placeholder: "Board model",
            tavola_anno_placeholder: "Board year",
            sacca_placeholder: "Bag",
            sacca_si: "Yes",
            sacca_no: "No",
            pagaia_placeholder: "Paddle (brand and model)",
            altro_placeholder: "Other equipment left",
            dati_documento_title: "Document Details",
            adulto_kid_placeholder: "Adult or Kid",
            tipo_rimessaggio_placeholder: "Storage type",
            tr_settimanale: "Weekly (€90 + €10 membership fee)",
            tr_mensile: "Monthly (€180 + €10 membership fee)",
            tr_annuale: "Annual (€450)",
            acconto_placeholder: "Deposit €",
            data_versamento_label: "Deposit payment date",
            ricevuta_label: "Payment receipt",
            ricevuta_help: "Upload the payment receipt as PDF or image.",
            salva_rimessaggio_btn: "Save storage booking",
            privacy_alert: "You must accept the privacy policy terms to submit the form.",
            date_format_alert: "Enter the date in dd/mm/yyyy format."
        },
        fr: {
            switcher_label: "Choisissez la langue",
            nome_placeholder: "Prénom",
            cognome_placeholder: "Nom",
            luogo_nascita_placeholder: "Lieu de naissance",
            data_nascita_label: "Date de naissance",
            indirizzo_residenza_label: "Adresse de résidence",
            indirizzo_residenza_placeholder: "Adresse de résidence",
            indirizzo_label: "Adresse",
            indirizzo_placeholder: "Adresse",
            citta_placeholder: "Ville",
            cap_placeholder: "Code postal",
            telefono_label: "Téléphone",
            telefono_placeholder: "333 0000000",
            email_label: "E-mail",
            email_placeholder: "E-mail",
            tipo_documento_placeholder: "Type de document",
            doc_carta_identita: "Carte d'identité",
            doc_carta_identita_alt: "Carte d'identité",
            doc_codice_fiscale: "Code fiscal",
            doc_patente: "Permis de conduire",
            doc_passaporto: "Passeport",
            numero_documento_placeholder: "Numéro de document",
            privacy_text: "J'accepte les conditions de la politique de confidentialité",
            privacy_hint: "(Lisez la politique de confidentialité pour activer)",
            privacy_link_text: "Lire la politique de confidentialité",
            firma_label: "Signature numérique",
            firma_placeholder_text: "🖌️ Touchez ici pour signer",
            firma_modal_title: "Signez ici",
            firma_modal_subtitle: "Utilisez votre doigt ou la souris pour signer dans l'espace blanc",
            btn_conferma: "Confirmer",
            btn_cancella: "Effacer",
            btn_chiudi: "Fermer",
            firma_caricata_label: "Signature téléchargée",
            date_placeholder: "jj/mm/aaaa",
            iscrizione_title: "Formulaire d'inscription Moki Club Numana",
            categoria_placeholder: "Adulte ou Enfant",
            categoria_adulto: "Adulte",
            categoria_kid: "Enfant",
            tipo_iscrizione_placeholder: "Type d'inscription",
            ti_iscrizione: "Inscription",
            ti_iscrizione_noleggio: "Inscription + Location",
            ti_abbonamento: "Abonnement",
            ti_lezione: "Cours",
            ti_alba: "Session lever du soleil",
            ti_notturna: "Session nocturne",
            newsletter_text: "Je souhaite recevoir des communications par e-mail",
            sa_nuotare_text: "Je déclare savoir nager",
            invia_iscrizione_btn: "Envoyer l'inscription",
            rimessaggio_title: "Formulaire de stockage de planche Moki Club Numana",
            dati_tavola_title: "Détails de la planche",
            tavola_marca_placeholder: "Marque de la planche",
            tavola_modello_placeholder: "Modèle de la planche",
            tavola_anno_placeholder: "Année de la planche",
            sacca_placeholder: "Housse",
            sacca_si: "Oui",
            sacca_no: "Non",
            pagaia_placeholder: "Pagaie (marque et modèle)",
            altro_placeholder: "Autre matériel laissé",
            dati_documento_title: "Détails du document",
            adulto_kid_placeholder: "Adulte ou Enfant",
            tipo_rimessaggio_placeholder: "Type de stockage",
            tr_settimanale: "Hebdomadaire (90 € + 10 € d'adhésion)",
            tr_mensile: "Mensuel (180 € + 10 € d'adhésion)",
            tr_annuale: "Annuel (450 €)",
            acconto_placeholder: "Acompte €",
            data_versamento_label: "Date de versement de l'acompte",
            ricevuta_label: "Reçu de paiement",
            ricevuta_help: "Téléchargez le reçu de paiement au format PDF ou image.",
            salva_rimessaggio_btn: "Enregistrer le stockage",
            privacy_alert: "Vous devez accepter les conditions de la politique de confidentialité pour envoyer le formulaire.",
            date_format_alert: "Saisissez la date au format jj/mm/aaaa."
        },
        es: {
            switcher_label: "Selecciona el idioma",
            nome_placeholder: "Nombre",
            cognome_placeholder: "Apellido",
            luogo_nascita_placeholder: "Lugar de nacimiento",
            data_nascita_label: "Fecha de nacimiento",
            indirizzo_residenza_label: "Dirección de residencia",
            indirizzo_residenza_placeholder: "Dirección de residencia",
            indirizzo_label: "Dirección",
            indirizzo_placeholder: "Dirección",
            citta_placeholder: "Ciudad",
            cap_placeholder: "Código postal",
            telefono_label: "Teléfono",
            telefono_placeholder: "333 0000000",
            email_label: "Correo electrónico",
            email_placeholder: "Correo electrónico",
            tipo_documento_placeholder: "Tipo de documento",
            doc_carta_identita: "Documento de identidad",
            doc_carta_identita_alt: "Documento de identidad",
            doc_codice_fiscale: "Código fiscal",
            doc_patente: "Carné de conducir",
            doc_passaporto: "Pasaporte",
            numero_documento_placeholder: "Número de documento",
            privacy_text: "Acepto las condiciones de la política de privacidad",
            privacy_hint: "(Lee la política de privacidad para habilitar)",
            privacy_link_text: "Leer la política de privacidad",
            firma_label: "Firma digital",
            firma_placeholder_text: "🖌️ Toca aquí para firmar",
            firma_modal_title: "Firma aquí",
            firma_modal_subtitle: "Usa el dedo o el ratón para firmar en el espacio blanco",
            btn_conferma: "Confirmar",
            btn_cancella: "Borrar",
            btn_chiudi: "Cerrar",
            firma_caricata_label: "Firma cargada",
            date_placeholder: "dd/mm/aaaa",
            iscrizione_title: "Formulario de inscripción Moki Club Numana",
            categoria_placeholder: "Adulto o Niño",
            categoria_adulto: "Adulto",
            categoria_kid: "Niño",
            tipo_iscrizione_placeholder: "Tipo de inscripción",
            ti_iscrizione: "Inscripción",
            ti_iscrizione_noleggio: "Inscripción + Alquiler",
            ti_abbonamento: "Abono",
            ti_lezione: "Clase",
            ti_alba: "Sesión al amanecer",
            ti_notturna: "Sesión nocturna",
            newsletter_text: "Quiero recibir comunicaciones por correo electrónico",
            sa_nuotare_text: "Declaro que sé nadar",
            invia_iscrizione_btn: "Enviar inscripción",
            rimessaggio_title: "Formulario de almacenamiento de tabla Moki Club Numana",
            dati_tavola_title: "Datos de la tabla",
            tavola_marca_placeholder: "Marca de la tabla",
            tavola_modello_placeholder: "Modelo de la tabla",
            tavola_anno_placeholder: "Año de la tabla",
            sacca_placeholder: "Funda",
            sacca_si: "Sí",
            sacca_no: "No",
            pagaia_placeholder: "Remo (marca y modelo)",
            altro_placeholder: "Otro material dejado",
            dati_documento_title: "Datos del documento",
            adulto_kid_placeholder: "Adulto o Niño",
            tipo_rimessaggio_placeholder: "Tipo de almacenamiento",
            tr_settimanale: "Semanal (90€ + 10€ de inscripción)",
            tr_mensile: "Mensual (180€ + 10€ de inscripción)",
            tr_annuale: "Anual (450€)",
            acconto_placeholder: "Anticipo €",
            data_versamento_label: "Fecha de pago del anticipo",
            ricevuta_label: "Recibo de pago",
            ricevuta_help: "Sube el recibo de pago en formato PDF o imagen.",
            salva_rimessaggio_btn: "Guardar almacenamiento",
            privacy_alert: "Debes aceptar las condiciones de la política de privacidad para enviar el formulario.",
            date_format_alert: "Introduce la fecha en formato dd/mm/aaaa."
        },
        de: {
            switcher_label: "Sprache wählen",
            nome_placeholder: "Vorname",
            cognome_placeholder: "Nachname",
            luogo_nascita_placeholder: "Geburtsort",
            data_nascita_label: "Geburtsdatum",
            indirizzo_residenza_label: "Wohnadresse",
            indirizzo_residenza_placeholder: "Wohnadresse",
            indirizzo_label: "Adresse",
            indirizzo_placeholder: "Adresse",
            citta_placeholder: "Stadt",
            cap_placeholder: "Postleitzahl",
            telefono_label: "Telefon",
            telefono_placeholder: "333 0000000",
            email_label: "E-Mail",
            email_placeholder: "E-Mail",
            tipo_documento_placeholder: "Dokumententyp",
            doc_carta_identita: "Personalausweis",
            doc_carta_identita_alt: "Personalausweis",
            doc_codice_fiscale: "Steuernummer",
            doc_patente: "Führerschein",
            doc_passaporto: "Reisepass",
            numero_documento_placeholder: "Dokumentennummer",
            privacy_text: "Ich akzeptiere die Bedingungen der Datenschutzrichtlinie",
            privacy_hint: "(Lesen Sie die Datenschutzrichtlinie zur Aktivierung)",
            privacy_link_text: "Datenschutzrichtlinie lesen",
            firma_label: "Digitale Unterschrift",
            firma_placeholder_text: "🖌️ Hier tippen, um zu unterschreiben",
            firma_modal_title: "Hier unterschreiben",
            firma_modal_subtitle: "Verwenden Sie Ihren Finger oder die Maus, um im weißen Bereich zu unterschreiben",
            btn_conferma: "Bestätigen",
            btn_cancella: "Löschen",
            btn_chiudi: "Schließen",
            firma_caricata_label: "Hochgeladene Unterschrift",
            date_placeholder: "tt/mm/jjjj",
            iscrizione_title: "Anmeldeformular Moki Club Numana",
            categoria_placeholder: "Erwachsener oder Kind",
            categoria_adulto: "Erwachsener",
            categoria_kid: "Kind",
            tipo_iscrizione_placeholder: "Art der Anmeldung",
            ti_iscrizione: "Anmeldung",
            ti_iscrizione_noleggio: "Anmeldung + Verleih",
            ti_abbonamento: "Abonnement",
            ti_lezione: "Kurs",
            ti_alba: "Sonnenaufgangs-Session",
            ti_notturna: "Nacht-Session",
            newsletter_text: "Ich möchte E-Mail-Mitteilungen erhalten",
            sa_nuotare_text: "Ich erkläre, dass ich schwimmen kann",
            invia_iscrizione_btn: "Anmeldung senden",
            rimessaggio_title: "Formular für Boardlagerung Moki Club Numana",
            dati_tavola_title: "Angaben zum Board",
            tavola_marca_placeholder: "Board-Marke",
            tavola_modello_placeholder: "Board-Modell",
            tavola_anno_placeholder: "Baujahr des Boards",
            sacca_placeholder: "Tasche",
            sacca_si: "Ja",
            sacca_no: "Nein",
            pagaia_placeholder: "Paddel (Marke und Modell)",
            altro_placeholder: "Sonstiges zurückgelassenes Material",
            dati_documento_title: "Angaben zum Dokument",
            adulto_kid_placeholder: "Erwachsener oder Kind",
            tipo_rimessaggio_placeholder: "Art der Lagerung",
            tr_settimanale: "Wöchentlich (90€ + 10€ Mitgliedschaft)",
            tr_mensile: "Monatlich (180€ + 10€ Mitgliedschaft)",
            tr_annuale: "Jährlich (450€)",
            acconto_placeholder: "Anzahlung €",
            data_versamento_label: "Datum der Anzahlung",
            ricevuta_label: "Zahlungsbeleg",
            ricevuta_help: "Laden Sie den Zahlungsbeleg als PDF oder Bild hoch.",
            salva_rimessaggio_btn: "Lagerung speichern",
            privacy_alert: "Sie müssen die Bedingungen der Datenschutzrichtlinie akzeptieren, um das Formular zu senden.",
            date_format_alert: "Geben Sie das Datum im Format tt/mm/jjjj ein."
        }
    };

    function getSavedLang() {
        try {
            var saved = window.localStorage.getItem(STORAGE_KEY);
            if (saved && SUPPORTED.indexOf(saved) !== -1) {
                return saved;
            }
        } catch (e) {
            /* localStorage non disponibile: si ignora e si usa il default */
        }
        return null;
    }

    function saveLang(lang) {
        try {
            window.localStorage.setItem(STORAGE_KEY, lang);
        } catch (e) {
            /* ignora se non disponibile */
        }
    }

    function detectBrowserLang() {
        var nav = window.navigator.language || window.navigator.userLanguage || DEFAULT_LANG;
        var code = nav.slice(0, 2).toLowerCase();
        return SUPPORTED.indexOf(code) !== -1 ? code : DEFAULT_LANG;
    }

    function t(key, lang) {
        var dict = translations[lang] || translations[DEFAULT_LANG];
        if (dict && Object.prototype.hasOwnProperty.call(dict, key)) {
            return dict[key];
        }
        return translations[DEFAULT_LANG][key] || "";
    }

    function applyLanguage(lang) {
        if (SUPPORTED.indexOf(lang) === -1) {
            lang = DEFAULT_LANG;
        }

        var dict = translations[lang];

        // Testo semplice (contenuto dell'elemento)
        var textNodes = document.querySelectorAll("[data-i18n]");
        textNodes.forEach(function (el) {
            var key = el.getAttribute("data-i18n");
            if (dict && Object.prototype.hasOwnProperty.call(dict, key)) {
                el.textContent = dict[key];
            }
        });

        // Placeholder di input/textarea
        var placeholderNodes = document.querySelectorAll("[data-i18n-placeholder]");
        placeholderNodes.forEach(function (el) {
            var key = el.getAttribute("data-i18n-placeholder");
            if (dict && Object.prototype.hasOwnProperty.call(dict, key)) {
                el.setAttribute("placeholder", dict[key]);
            }
        });

        // aria-label
        var ariaNodes = document.querySelectorAll("[data-i18n-aria]");
        ariaNodes.forEach(function (el) {
            var key = el.getAttribute("data-i18n-aria");
            if (dict && Object.prototype.hasOwnProperty.call(dict, key)) {
                el.setAttribute("aria-label", dict[key]);
            }
        });

        document.documentElement.setAttribute("lang", lang);

        // Aggiorna lo stato visivo del selettore
        var buttons = document.querySelectorAll(".lang-switcher-btn");
        buttons.forEach(function (btn) {
            var isActive = btn.getAttribute("data-lang") === lang;
            btn.classList.toggle("is-active", isActive);
            btn.setAttribute("aria-pressed", isActive ? "true" : "false");
        });

        // Espone traduzioni utili anche agli altri script della pagina
        // (es. messaggi di validazione nei form iscrizione/rimessaggio)
        window.mokiI18n = {
            lang: lang,
            t: function (key) {
                return t(key, lang);
            }
        };

        saveLang(lang);
        document.dispatchEvent(new CustomEvent("moki:langchange", { detail: { lang: lang } }));
    }

    function buildSwitcher(container, currentLang) {
        var wrap = document.createElement("div");
        wrap.className = "lang-switcher";
        wrap.setAttribute("role", "group");
        wrap.setAttribute("aria-label", "Selettore lingua");

        var label = document.createElement("span");
        label.className = "lang-switcher-label";
        label.setAttribute("data-i18n", "switcher_label");
        label.textContent = t("switcher_label", currentLang);
        wrap.appendChild(label);

        var btnRow = document.createElement("div");
        btnRow.className = "lang-switcher-row";

        SUPPORTED.forEach(function (code) {
            var meta = LANG_META[code];
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "lang-switcher-btn";
            btn.setAttribute("data-lang", code);
            btn.setAttribute("aria-pressed", code === currentLang ? "true" : "false");
            btn.innerHTML =
                '<span class="lang-switcher-flag" aria-hidden="true">' + meta.flag + "</span>" +
                '<span class="lang-switcher-code">' + meta.label + "</span>";
            btn.addEventListener("click", function () {
                applyLanguage(code);
            });
            btnRow.appendChild(btn);
        });

        wrap.appendChild(btnRow);
        container.appendChild(wrap);
    }

    function init() {
        var container = document.querySelector("[data-lang-switcher]");
        if (!container) {
            return;
        }

        var startLang = getSavedLang() || detectBrowserLang();
        buildSwitcher(container, startLang);
        applyLanguage(startLang);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
