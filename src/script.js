// Calendar and info elements
const calendarEl = document.getElementById("calendar");
const errorsEl = document.getElementById("errors");
const dateText = document.getElementById("dateText");
const rainText = document.getElementById("rainText");
const freeText = document.getElementById("freeText");

// Fetch data for a given date (YYYY-MM-DD)
async function fetchByDate(date) {
  const d = date.toISOString().slice(0, 10); // YYYY-MM-DD
  const res = await fetch(`/backend/api/getByDate.php?date=${d}`);
  if (!res.ok) throw new Error("API error");
  return res.json();
}

let viewDate = new Date(); // currently displayed month
let selectedDate = null; // user-selected date

function formatMonth(d) {
  return d.toLocaleDateString("de-CH", { month: "long", year: "numeric" });
}
function toYmd(d) {
  const z = (n) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${z(d.getMonth() + 1)}-${z(d.getDate())}`;
}
function startOfMonth(d) {
  return new Date(d.getFullYear(), d.getMonth(), 1);
}
function endOfMonth(d) {
  return new Date(d.getFullYear(), d.getMonth() + 1, 0);
}
function isFuture(d) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return d > today;
}

function renderCalendar() {
  calendarEl.innerHTML = "";

  // Kopf
  const head = document.createElement("div");
  head.className = "cal-head";
  const prev = Object.assign(document.createElement("button"), { className: "cal-btn", innerText: "‹" });
  const month = Object.assign(document.createElement("div"), { className: "cal-month", textContent: formatMonth(viewDate) });
  const next = Object.assign(document.createElement("button"), { className: "cal-btn", innerText: "›" });

  prev.onclick = () => {
    viewDate.setMonth(viewDate.getMonth() - 1);
    renderCalendar();
  };
  next.onclick = () => {
    viewDate.setMonth(viewDate.getMonth() + 1);
    renderCalendar();
  };

  head.append(prev, month, next);
  calendarEl.append(head);

  // Wochentage
  const grid = document.createElement("div");
  grid.className = "cal-grid";
  ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"].forEach((d) => {
    const el = document.createElement("div");
    el.className = "cal-dow";
    el.textContent = d;
    grid.append(el);
  });

  // Tage
  const first = startOfMonth(viewDate);
  const last = endOfMonth(viewDate);
  const pad = (first.getDay() + 6) % 7; // Montag=0
  for (let i = 0; i < pad; i++) {
    grid.append(document.createElement("div"));
  }

  for (let day = 1; day <= last.getDate(); day++) {
    const d = new Date(viewDate.getFullYear(), viewDate.getMonth(), day);
    const el = document.createElement("div");
    el.className = "cal-day";
    el.textContent = String(day);

    if (isFuture(d)) el.classList.add("disabled");
    const t = new Date();
    t.setHours(0, 0, 0, 0);
    if (d.getTime() === t.getTime()) el.classList.add("today");
    if (selectedDate && toYmd(d) === toYmd(selectedDate)) el.classList.add("selected");

    el.onclick = () => {
      selectedDate = d;
      errorsEl.textContent = "";
      renderCalendar();
      // Beispiel: Text oben aktualisieren
      dateText.textContent = d.toLocaleDateString("de-CH");
    };
    grid.append(el);
  }
  calendarEl.append(grid);
}
renderCalendar();

// klick

document.getElementById("queryBtn")?.addEventListener("click", async () => {
  if (!selectedDate) {
    errorsEl.textContent = "Bitte ein Datum wählen.";
    return;
  }
  if (isFuture(selectedDate)) {
    errorsEl.textContent = "Nur Daten aus der Vergangenheit sind erlaubt.";
    return;
  }

  errorsEl.textContent = "Lade Daten…";
  try {
    const data = await fetchByDate(selectedDate);
    const rows = data.records || [];

    if (rows.length === 0) {
      errorsEl.textContent = "Keine Einträge für dieses Datum.";
      rainText.textContent = "0.0";
      freeText.textContent = "0";
      return;
    }

    const avgRain = rows.reduce((a, r) => a + (r.rain_mm_h ?? 0), 0) / rows.length;
    const freeSum = rows.reduce((a, r) => a + (r.free_spaces ?? 0), 0);

    rainText.textContent = avgRain.toFixed(1);
    freeText.textContent = freeSum.toLocaleString("de-CH");
    errorsEl.textContent = "";
  } catch (e) {
    console.error(e);
    errorsEl.textContent = "Daten konnten nicht geladen werden.";
  }
});
// Horn control
const hornButton = document.getElementById("hornButton");
const hornSound = document.getElementById("hornSound");

hornButton?.addEventListener("click", () => {
  hornSound.currentTime = 0;
  hornSound.play();
});
