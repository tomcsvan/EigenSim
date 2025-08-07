import csv
import math
import sys
from datetime import datetime
from statistics import mean, stdev


class ReportStats:
    def __init__(self):
        self.generated_at = ""
        self.holdings = 0
        self.total_return = 0.0
        self.annualized_return = 0.0
        self.sharpe_ratio = 0.0
        self.max_drawdown = 0.0
        self.win_rate = 0.0
        self.trade_count = 0
        self.t_stat = 0.0
        self.p_value = 0.0
        self.confidence_95_low = 0.0
        self.confidence_95_high = 0.0

    def to_dict(self):
        return {
            'generated_at': self.generated_at,
            'holdings': self.holdings,
            'total_return': round(self.total_return, 4),
            'annualized_return': round(self.annualized_return, 4),
            'sharpe_ratio': round(self.sharpe_ratio, 4),
            'max_drawdown': round(self.max_drawdown, 4),
            'win_rate': round(self.win_rate, 4),
            'trade_count': self.trade_count,
            't_stat': round(self.t_stat, 4),
            'p_value': round(self.p_value, 4),
            'confidence_95_low': round(self.confidence_95_low, 4),
            'confidence_95_high': round(self.confidence_95_high, 4)
        }


def read_trades(trades_file):
    trades = []
    with open(trades_file, newline='') as csvfile:
        reader = csv.DictReader(csvfile)
        for row in reader:
            trades.append({
                'side': row['side'].strip().upper(),
                'quantity': int(row['quantity']),
                'price': float(row['price']),
                'time': row['time']
            })
    return trades


def calculate_metrics(trades):
    TRANSACTION_FEE = 1.0
    matched_pairs = []
    total_transaction_fees = 0.0

    for i in range(0, len(trades), 2):
        buy = trades[i]
        sell = trades[i + 1]

        matched_pairs.append({
            'quantity': buy['quantity'],
            'buy_price': buy['price'],
            'sell_price': sell['price']
        })
        total_transaction_fees += TRANSACTION_FEE * 2

    returns = []
    total_return_value = 0.0
    for pair in matched_pairs:
        profit = (pair['sell_price'] - pair['buy_price']) * pair['quantity']
        returns.append(profit)
        total_return_value += profit

    total_return_value -= total_transaction_fees

    stats = ReportStats()
    stats.generated_at = datetime.now().isoformat()
    stats.holdings = 0
    stats.trade_count = len(trades)
    stats.total_return = total_return_value

    if returns:
        avg_return = mean(returns)
        stats.annualized_return = avg_return * 252
        stats.win_rate = sum(1 for r in returns if r > 0) / len(returns)

        if len(returns) > 1:
            risk_free_daily = 0.02 / 252
            excess_returns = [r - risk_free_daily for r in returns]
            stats.sharpe_ratio = mean(excess_returns) / stdev(excess_returns) * math.sqrt(252)

            # Drawdown
            cumulative = []
            total = 0.0
            for r in returns:
                total += r
                cumulative.append(total)
            peak = cumulative[0]
            max_dd = 0.0
            for val in cumulative:
                peak = max(peak, val)
                max_dd = max(max_dd, peak - val)
            stats.max_drawdown = max_dd

            # T-stat and CI
            n = len(returns)
            sem = stdev(returns) / math.sqrt(n)
            stats.t_stat = avg_return / sem if sem != 0 else 0.0
            t_val = 2.0
            stats.confidence_95_low = avg_return - t_val * sem
            stats.confidence_95_high = avg_return + t_val * sem

            # pval
            stats.p_value = 2 * (1 - 0.5 * (1 + math.erf(abs(stats.t_stat) / math.sqrt(2))))

    return stats


def main():
    if len(sys.argv) < 2:
        print("Missing input file")
        sys.exit(1)

    trades_file = sys.argv[1]
    trades = read_trades(trades_file)
    stats = calculate_metrics(trades)
    values = stats.to_dict()

    print(values['total_return'])
    print(values['annualized_return'])
    print(values['sharpe_ratio'])
    print(values['max_drawdown'])
    print(values['win_rate'])
    print(values['trade_count'])
    print(values['t_stat'])
    print(values['p_value'])
    print(values['confidence_95_low'])
    print(values['confidence_95_high'])


if __name__ == "__main__":
    main()
