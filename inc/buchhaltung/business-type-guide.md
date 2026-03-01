# Betriebstyp & Auto-Konfiguration

Das System konfiguriert sich automatisch basierend auf Ihrem Betriebstyp.

## 🇩🇪 Deutsche Betriebstypen

### 1️⃣ § 19 Kleinunternehmer
**Umsatzschwelle:** < 22.500€ Jahresumsatz

**Automatische Anpassungen:**
- ✅ Steuersätze auf 0% gesetzt (informativ)
- ✅ Keine Umsatzsteuer-ID erforderlich
- ✅ Rechnungen ohne USt-Ausweis
- ✅ Audit-Log ohne Steuer-Details
- ✅ Vereinfachte Buchhaltung

**Buchhaltung:**
- Einnahme-Überschuss-Rechnung (EÜR) oder Bilanz möglich
- Keine Vorsteuer abziehbar
- Keine UStVA erforderlich

**Was zu beachten:**
> Hinweis auf Rechnung: "Kleine Lieferer nach §19 UStG"

---

### 2️⃣ Standard Unternehmer (mit USt-ID)
**Umsatzschwelle:** ≥ 22.500€ Jahresumsatz

**Automatische Anpassungen:**
- ✅ Alle Steuersätze aktiviert (19%, 7%, 0%)
- ✅ Umsatzsteuer-ID erforderlich
- ✅ Rechnungen mit Umsatzsteuer-Ausweis
- ✅ Vorsteuer-Abzug möglich
- ✅ Monatliche UStVA erforderlich

**Buchhaltung:**
- Doppelte Buchführung (Bilanz)
- Vorsteuer-Abzug
- UStVA-Export für Finanzamt
- SKR04-Konten Integration

**Was zu beachten:**
> USt-ID auf jeder Rechnung erforderlich

---

### 3️⃣ Freiberufler/Künstler
**Status:** USt-Befreiung möglich

**Automatische Anpassungen:**
- ✅ Steuersätze optional (0% Standard)
- ✅ USt-ID optional
- ✅ Vereinfachte Rechnungen
- ✅ EÜR-Export
- ✅ Weniger Buchhaltungs-Komplexität

**Buchhaltung:**
- Einnahme-Überschuss-Rechnung (EÜR)
- Keine Vorsteuer normalerweise
- EÜR für Finanzamt statt UStVA

**Was zu beachten:**
> Prüfen Sie mit dem Finanzamt die Befreiung!

---

## ⚙️ Was sich automatisch ändert?

| Feature | Kleinunternehmer | Standard | Freiberufler |
|---------|------------------|----------|--------------|
| Steuersätze | 0% (nur) | 19%, 7%, 0%, §13b, IGE | 0% (optional) |
| USt-ID | Nicht erforderlich | ✓ Erforderlich | Optional |
| Rechnungs-USt | Nein | Ja | Optional |
| Vorsteuer-Abzug | Nein | Ja | Nein |
| UStVA | Nicht erforderlich | ✓ Monatlich | Nicht erforderlich |
| EÜR/Bilanz | EÜR möglich | Bilanz erforderlich | EÜR |
| Audit-Log | Vereinfacht | Vollständig | Vereinfacht |

---

## 🔄 Wechsel des Betriebstyps

1. Gehen Sie zu **Einstellungen → Geschäftliche Grunddaten**
2. Wählen Sie einen anderen Betriebstyp
3. Das System konfiguriert sich automatisch neu

**Hinweis:** Bestehende Transaktionen werden nicht automatisch angepasst. Weitere Einstellungen können manuell in der Buchhaltung angepasst werden.

---

## 📞 Support

Fragen zu Ihrem Betriebstyp? Kontaktieren Sie einen **Steuerberater** um sicherzustellen, dass die Konfiguration korrekt ist.
