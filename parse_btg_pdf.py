import argparse
import csv
import os
import re
import subprocess
import tempfile
from datetime import datetime
from typing import List, Tuple

import pdfplumber

Record = Tuple[str, str, float]


def _extract_lines(pdf_path: str) -> List[str]:
    """Return all text lines extracted from the PDF."""
    lines: List[str] = []
    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            text = page.extract_text() or ""
            for line in text.splitlines():
                lines.append(line.strip())
    return lines


def _parse_lines(lines: List[str]) -> List[Record]:
    """Parse lines into unified records."""
    records: List[Record] = []
    for line in lines:
        parts = re.split(r"(?:\s{2,}|\t+)", line.strip())
        parts = [p for p in parts if p]
        if len(parts) < 3:
            continue
        raw_date = parts[0]
        raw_value = parts[-1]
        description = " ".join(parts[1:-1])

        try:
            date = datetime.strptime(raw_date, "%d/%m/%Y").strftime("%Y-%m-%d")
        except ValueError:
            date = raw_date

        clean_value = (
            raw_value.replace("R$", "")
            .replace(".", "")
            .replace(",", ".")
        )
        try:
            value = -abs(float(clean_value))
        except ValueError:
            continue

        records.append((date, description, value))
    return records


def _export_csv(records: List[Record]) -> str:
    """Export records to a temporary CSV and return its path."""
    tmp = tempfile.NamedTemporaryFile("w", suffix=".csv", delete=False, newline="", encoding="utf-8")
    writer = csv.writer(tmp)
    writer.writerow(["data", "titulo", "valor"])
    for date, desc, value in records:
        writer.writerow([date, desc, f"{value:.2f}"])
    tmp.close()
    return tmp.name


def _import_csv(csv_path: str) -> None:
    """Reuse existing PHP importer to ingest the CSV."""
    script_path = os.path.join(os.path.dirname(__file__), "importar.php")
    php_code = f"require '{script_path}'; parse_nubank_csv('{csv_path}');"
    subprocess.run(["php", "-r", php_code], check=True)


def parse_btg_pdf(pdf_path: str, run_import: bool = True) -> str:
    """Parse BTG PDF and optionally import via existing flow.

    Returns the path to the generated temporary CSV."""
    lines = _extract_lines(pdf_path)
    records = _parse_lines(lines)
    csv_path = _export_csv(records)
    if run_import:
        _import_csv(csv_path)
    return csv_path


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Extrai transações de um PDF do BTG e reutiliza fluxo de importação."
    )
    parser.add_argument("pdf", help="Caminho para o PDF do extrato do BTG.")
    parser.add_argument(
        "--no-import",
        action="store_true",
        help="Apenas gera o CSV temporário sem importar para o banco de dados.",
    )
    args = parser.parse_args()
    csv_file = parse_btg_pdf(args.pdf, run_import=not args.no_import)
    print(csv_file)
