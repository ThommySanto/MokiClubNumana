(function () {
    "use strict";

    var grid = document.querySelector(".evento-tavole-grid");
    var riepilogo = document.getElementById("riepilogo-tavole");
    var inputPostiJson = document.getElementById("input-posti-json");
    var inputModalita = document.getElementById("input-modalita");
    var inputTipoPartecipazione = document.getElementById("input-tipo-partecipazione");
    var form = document.getElementById("form-prenotazione");
    var sezioneTavole = document.getElementById("sezione-tavole");
    var toggleInputs = document.querySelectorAll(".evento-toggle-input");
    var modalitaQuota = document.body.getAttribute("data-modalita-tariffe") || "dettagliata";

    if (!grid || !form) {
        return;
    }

    var selezione = {};

    function getTipoPartecipazione() {
        if (modalitaQuota === "semplice") {
            return "noleggio";
        }

        var selezionato = document.querySelector("input[name='tipo-partecipazione']:checked");
        return selezionato ? selezionato.value : "noleggio";
    }

    function mostraNascondiTavole() {
        if (modalitaQuota === "semplice") {
            if (sezioneTavole) sezioneTavole.style.display = "block";
            inputTipoPartecipazione.value = "noleggio";
            leggiSelezione();
            renderRiepilogo();
            return;
        }

        var tipoPartecipazione = getTipoPartecipazione();
        inputTipoPartecipazione.value = tipoPartecipazione;

        if (tipoPartecipazione === "propria") {
            sezioneTavole.style.display = "none";
            riepilogo.innerHTML = '<p class="evento-riepilogo-vuoto">Porterai la tua tavola. Aggiungi i partecipanti nel modulo sottostante.</p>';
            inputPostiJson.value = "[]";
            inputModalita.value = "singola";
        } else {
            sezioneTavole.style.display = "block";
            leggiSelezione();
            renderRiepilogo();
        }
    }

    function leggiSelezione() {
        selezione = {};
        if (getTipoPartecipazione() === "propria") {
            return;
        }

        var cards = grid.querySelectorAll(".evento-tavola-card");
        cards.forEach(function (card) {
            var id = card.getAttribute("data-tavola-id");
            var nome = card.getAttribute("data-tavola-nome");
            var disponibili = parseInt(card.getAttribute("data-posti-disponibili"), 10) || 0;
            var qty = 0;

            var checkbox = card.querySelector(".evento-tavola-checkbox");
            var numberInput = card.querySelector(".evento-tavola-qty-input");

            if (checkbox) {
                qty = checkbox.checked ? 1 : 0;
            } else if (numberInput) {
                qty = Math.max(0, Math.min(disponibili, parseInt(numberInput.value, 10) || 0));
                numberInput.value = qty;
            }

            if (qty > 0) {
                selezione[id] = { nome: nome, quantita: qty };
            }
        });
    }

    function renderRiepilogo() {
        riepilogo.innerHTML = "";
        var totalePosti = 0;
        var idsOrdinati = Object.keys(selezione);

        if (idsOrdinati.length === 0) {
            riepilogo.innerHTML = '<p class="evento-riepilogo-vuoto">Nessuna tavola selezionata.</p>';
            inputPostiJson.value = "[]";
            inputModalita.value = "singola";
            return;
        }

        var struttura = [];

        idsOrdinati.forEach(function (id) {
            var info = selezione[id];
            for (var i = 0; i < info.quantita; i++) {
                totalePosti++;
                var blocco = document.createElement("div");
                blocco.className = "evento-riepilogo-persona";
                blocco.innerHTML =
                    '<h4>' + info.nome + ' — Persona ' + (i + 1) + '</h4>' +
                    '<div class="form-row">' +
                    '<input type="text" placeholder="Nome" class="riepilogo-nome" required autocomplete="off">' +
                    '<input type="text" placeholder="Cognome" class="riepilogo-cognome" required autocomplete="off">' +
                    '<input type="email" placeholder="Email " class="riepilogo-email" required autocomplete="off">' +
                    '<input type="tel" placeholder="Telefono " class="riepilogo-telefono" required autocomplete="off">' +
                    '</div>';
                blocco.setAttribute("data-tavola-id", id);
                riepilogo.appendChild(blocco);

                struttura.push({ evento_tavola_id: id, nome: "", cognome: "", email: "", telefono: "" });
            }
        });

        inputModalita.value = totalePosti > 1 ? "gruppo" : "singola";
        inputPostiJson.value = JSON.stringify(struttura);
    }

    grid.addEventListener("change", function (e) {
        if (e.target.classList.contains("evento-tavola-checkbox") || e.target.classList.contains("evento-tavola-qty-input")) {
            leggiSelezione();
            renderRiepilogo();
        }
    });
    grid.addEventListener("input", function (e) {
        if (e.target.classList.contains("evento-tavola-qty-input")) {
            leggiSelezione();
            renderRiepilogo();
        }
    });

    if (modalitaQuota === "semplice") {
        toggleInputs.forEach(function (input) {
            input.closest(".evento-toggle-label").style.display = "none";
            input.checked = false;
        });
        var radioNoleggio = document.querySelector("input[name='tipo-partecipazione'][value='noleggio']");
        if (radioNoleggio) {
            radioNoleggio.checked = true;
        }
    } else {
        toggleInputs.forEach(function (input) {
            input.addEventListener("change", mostraNascondiTavole);
        });
    }

    // ─── METODO DI PAGAMENTO (checkbox mutuamente esclusivi) ───
    var checkboxPagamento = document.querySelectorAll(".evento-pagamento-checkbox");
    checkboxPagamento.forEach(function (chk) {
        chk.addEventListener("change", function () {
            if (chk.checked) {
                checkboxPagamento.forEach(function (altro) {
                    if (altro !== chk) {
                        altro.checked = false;
                    }
                });
            }
        });
    });

    form.addEventListener("submit", function (e) {
        var pagamentoSelezionato = document.querySelectorAll(".evento-pagamento-checkbox:checked");
        if (pagamentoSelezionato.length !== 1) {
            e.preventDefault();
            alert("Seleziona un metodo di pagamento (bonifico oppure contanti/carta al Moki Club).");
            return;
        }

        var tipoPartecipazione = getTipoPartecipazione();
        inputTipoPartecipazione.value = tipoPartecipazione;

        if (tipoPartecipazione === "propria") {
            // Con tavola propria, basta il referente
            return;
        }

        leggiSelezione();

        var blocchi = riepilogo.querySelectorAll(".evento-riepilogo-persona");
        if (blocchi.length === 0) {
            e.preventDefault();
            alert("Seleziona almeno una tavola prima di inviare la prenotazione.");
            return;
        }

        var struttura = [];
        var valido = true;

        blocchi.forEach(function (blocco) {
            var nome = blocco.querySelector(".riepilogo-nome").value.trim();
            var cognome = blocco.querySelector(".riepilogo-cognome").value.trim();
            var email = blocco.querySelector(".riepilogo-email").value.trim();
            var telefono = blocco.querySelector(".riepilogo-telefono").value.trim();
            var tavolaId = blocco.getAttribute("data-tavola-id");

            if (nome === "" || cognome === "") {
                valido = false;
            }

            struttura.push({ evento_tavola_id: tavolaId, nome: nome, cognome: cognome, email: email, telefono: telefono });
        });

        if (!valido) {
            e.preventDefault();
            alert("Inserisci nome e cognome per ogni persona/tavola selezionata.");
            return;
        }

        inputPostiJson.value = JSON.stringify(struttura);
        inputModalita.value = struttura.length > 1 ? "gruppo" : "singola";
    });

    mostraNascondiTavole();
})();
