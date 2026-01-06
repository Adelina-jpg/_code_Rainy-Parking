# Rainy Parking

## 1. Kurzbeschreibung

Die Web-App **Rainy Parking** kombiniert in Echtzeit Parkplatzdaten der Stadt Basel mit aktuellen Wettervorhersagen. Nutzerinnen und Nutzer sehen, wie viele Parkplätze in Basel frei sind und wie sich das Wetter auf die Nutzung von Parkhäusern auswirken kann. Durch die Verbindung von Mobilität und Wetter entsteht ein praktisches Tool, das nicht nur Informationen liefert, sondern auch zeigt, welchen Einfluss Umweltfaktoren auf das alltägliche Parkverhalten haben können.

---

## 2. Learnings

Wir haben viel über Teamarbeit gelernt: Wir waren anfangs sehr schnell unterwegs und hatten viele Ideen, haben aber erst später gemerkt, dass nicht alle APIs zu unserem Konzept passen. Dadurch haben wir gelernt, uns mehr Zeit bei der Ideensuche zu nehmen und unsere Auswahl besser abzustimmen.

Aufgrund eines unstrukturierten Aufbaus ging viel Zeit verloren. Rückblickend wäre es sinnvoll gewesen, von Beginn an eine klare rote Linie festzulegen, die Aufgaben eindeutig aufzuteilen und das Projekt frühzeitig gemeinsam mit einer fachkundigen Person zu strukturieren.

**Ergänzen (Beispieltexte):**
- mit GitHub Pages zu deployen  
- Figma für Prototypen einzusetzen  
- APIs strukturiert zu testen

---

## 3. Schwierigkeiten

- **APIs:** Es war nicht einfach, eine geeignete API zu finden, die zuverlässig funktioniert.
- **Technik:** Teilweise hatten wir Schwierigkeiten, den Code zu verstehen und die JSON-Daten korrekt auszulesen.
- **roter Faden**: Da die Struktur zu Beginn fehlte, wurden einzelne Schritte übersehen, was später zu Unübersichtlichkeit führte und externe Hilfe notwendig machte. Die nachträgliche Fehlersuche erwies sich deutlich aufwendiger, als wenn von Anfang an korrekt und systematisch gearbeitet worden wäre.

**Ergänzen (optional):**  
Wie habt ihr diese Probleme gelöst? Zum Beispiel durch Tutorials, gegenseitige Hilfe, ChatGPT oder Tests im Browser.

---

## 4. Ressourcen

### APIs
- Parking: <https://api.parkendd.de/Basel>
- Weather: <https://api.open-meteo.com/v1/forecast?latitude=47.5584&longitude=7.5733&hourly=temperature_2m,rain&current=temperature_2m,rain&ref=freepublicapis.com>

### Tools und Technologien
- Figma (Prototyping)
- JavaScript, HTML, CSS, PHP, SQL
- GitHub & GitHub Pages
- ChatGPT (Fragen und Debugging)
- externe Hilfe (IT-Ausbildner) 

---

## 5. Links

- **Projektname:** Rainy Parking
- **Figma Mockup:**  
  <https://www.figma.com/design/eQnoVjTLfJhwhbWJ6SGIvb/IM-III--Don’t-Fry-Today?node-id=13-3&t=ATr4q5pwMhfdtNE3-1>
- **GitHub Repository:**  
  <https://github.com/Adelina-jpg/Rainy-Parking.git>
- **Projekt-URL (Deployment):**
  <https://rainy-parking.adelina-mezenen.ch>

---

## 6. Deployment

- Cron-Job muss registriert werden. Beispiel: 0 * * * * /usr/bin/php /var/www/html/cron_fetch.php >> /var/log/rainy_parking.log 2>&1
- DB Connection Credentials müssen korrigiert werden. Noch besser wäre eine Auslagerung in Environment Variables. 
