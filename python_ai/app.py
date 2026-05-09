from flask import Flask, jsonify, request

app = Flask(__name__)


@app.post("/risk-score")
def risk_score():
    payload = request.get_json(silent=True) or {}
    amount = float(payload.get("amount", 0))
    tx_count = int(payload.get("transactions_last_hour", 0))
    tx_type = str(payload.get("transaction_type", "unknown"))

    score = 0.1
    reasons = []

    if amount >= 50000:
        score += 0.45
        reasons.append("large amount")
    elif amount >= 20000:
        score += 0.25
        reasons.append("medium-large amount")

    if tx_count >= 8:
        score += 0.35
        reasons.append("rapid transactions")

    if tx_type == "withdraw":
        score += 0.15
        reasons.append("withdrawal risk")

    score = min(score, 0.99)
    if score >= 0.7:
        level = "high"
    elif score >= 0.4:
        level = "medium"
    else:
        level = "low"

    return jsonify(
        {
            "risk_score": round(score, 2),
            "risk_level": level,
            "reason": ", ".join(reasons) if reasons else "normal pattern",
        }
    )


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=8001, debug=True)
