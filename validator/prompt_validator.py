#!/usr/bin/env python3
import sys
import re

def is_valid_prompt(prompt: str) -> bool:
    # Check if prompt contains both Buy and Sell sections
    if not has_required_sections(prompt):
        return False
    
    # Extract buy and sell conditions
    buy_condition, sell_condition = extract_conditions(prompt)
    if not buy_condition or not sell_condition:
        return False
    
    # Validate each condition, check supported indicators, operators, and proper formatting.
    if not is_valid_condition(buy_condition):
        return False
    
    if not is_valid_condition(sell_condition):
        return False
    
    return True

def has_required_sections(prompt: str) -> bool: # only support "Buy when:" and "Sell when:" and case-sensitive"
    return "Buy when:" in prompt and "Sell when:" in prompt

def extract_conditions(prompt: str) -> tuple:

    lines = prompt.strip().split('\n')
    buy_condition = ""
    sell_condition = ""
    
    for line in lines:
        line = line.strip()
        if line.startswith("Buy when:"):
            buy_condition = line.replace("Buy when:", "").strip()
        elif line.startswith("Sell when:"):
            sell_condition = line.replace("Sell when:", "").strip()
        elif buy_condition and not sell_condition and line: # enforces one condition per line, so when a BUY condition is of multiple lines, it will reject
            return "", ""
        elif sell_condition and line: # enforces one condition per line, no new condition after Sell when:
            return "", ""
    
    return buy_condition, sell_condition

def is_valid_condition(condition: str) -> bool:

    # Check if condition is not empty
    if not condition.strip():
        return False
    
    if not has_valid_indicators(condition):
        return False
    
    if not has_valid_operators(condition):
        return False

    if not has_correct_case(condition):
        return False

    if has_unsupported_features(condition):
        return False
    
    return True

def has_valid_indicators(condition: str) -> bool:

    basic_indicators = ['price', 'open', 'close', 'high', 'low', 'volume', 'MACD', 'signal']
    
    function_indicators = re.findall(r'\b(SMA|EMA|RSI)\(\d+\)', condition)
    
    # Remove SMA, EMA, RSI function calls from the condition
    temp_condition = condition
    for func_indicator in function_indicators:
        temp_condition = temp_condition.replace(func_indicator, ' ')
    
    # Extract remaining words
    words = re.findall(r'\b[a-zA-Z]+\b', temp_condition)
    
    for word in words:
        if word in ['AND', 'OR']: # Logical operators, ignore them
            continue
        if word in basic_indicators: # Valid indicators, ignore them
            continue
        return False # Invalid indicator found
    
    return True

def has_valid_operators(condition: str) -> bool:
    comparison_ops = ['>=', '<=', '==', '!=', '>', '<']
    logical_ops = ['AND', 'OR']

    # Check for standalone =， if the = is not part of ==, !=, >=, <=
    if re.search(r'(?<![!<>=])=(?![=])', condition):
        return False
    
    # Check for other invalid operators
    invalid_operators = ['<>', '+', '-', '*', '/', '%', '&&', '||']
    for invalid_op in invalid_operators:
        if invalid_op in condition:
            return False
    
    temp_condition = condition
    for op in comparison_ops:
        temp_condition = temp_condition.replace(op, ' ')

    for op in logical_ops:
        temp_condition = temp_condition.replace(op, ' ')
    
    # Remove indicators and numbers (including decimals) and whitespace
    temp_condition = re.sub(r'\b(price|open|close|high|low|volume|MACD|signal)\b', ' ', temp_condition)
    temp_condition = re.sub(r'\b(SMA|EMA|RSI)\(\d+\)', ' ', temp_condition)
    temp_condition = re.sub(r'\b\d+\.?\d*\b', ' ', temp_condition)  # Support decimal numbers
    temp_condition = re.sub(r'[\s\(\)]', '', temp_condition)
    
    return len(temp_condition.strip()) == 0 # No remaining characters means valid operators used

def has_correct_case(condition: str) -> bool:
    matches = re.findall(r'\b(and|or)\b', condition, flags=re.IGNORECASE)
    for m in matches:
        if m != m.upper(): 
            return False
    return True

# check nested expressions
def has_unsupported_features(condition: str) -> bool:
    # Remove valid （）
    temp_condition = re.sub(r'\b(SMA|EMA|RSI)\(\d+\)', '', condition)

    if '(' in temp_condition or ')' in temp_condition:
        return True
    
    if '||' in condition or '&&' in condition:  
        return True
    
    return False

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("FAIL")
        sys.exit(1)

    prompt = sys.argv[1]
    if is_valid_prompt(prompt):
        print("OK")
    else:
        print("FAIL")
