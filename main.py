import ccxt
import talib
import pandas as pd
import numpy as np

# Menghubungkan ke API Binance (contoh platform yang mendukung Forex)
exchange = ccxt.binance()

# Fungsi untuk mendapatkan data historis
def get_historical_data(symbol, timeframe='1h', limit=100):
    ohlcv = exchange.fetch_ohlcv(symbol, timeframe=timeframe, limit=limit)
    ohlcv_df = pd.DataFrame(ohlcv, columns=['timestamp', 'open', 'high', 'low', 'close', 'volume'])
    ohlcv_df['timestamp'] = pd.to_datetime(ohlcv_df['timestamp'], unit='ms')
    return ohlcv_df

# Fungsi untuk menghitung indikator teknikal
def calculate_indicators(data):
    # Moving Average (SMA)
    data['SMA'] = talib.SMA(data['close'], timeperiod=14)
    # Relative Strength Index (RSI)
    data['RSI'] = talib.RSI(data['close'], timeperiod=14)
    # MACD
    data['MACD'], data['MACD_signal'], _ = talib.MACD(data['close'], fastperiod=12, slowperiod=26, signalperiod=9)
    return data

# Fungsi untuk memprediksi pergerakan harga (naik atau turun)
def predict_price_movement(data):
    predicted_price = predict_price(data)
    last_price = data['close'].iloc[-1]
    
    if predicted_price > last_price:
        direction = "NAIK"
    else:
        direction = "TURUN"
    
    return direction, predicted_price

# Fungsi untuk membuat prediksi harga menggunakan Linear Regression (sebagai model sederhana)
def predict_price(data):
    # Menggunakan harga penutupan untuk memprediksi
    X = np.array(range(len(data))).reshape(-1, 1)  # Waktu (indeks)
    y = data['close'].values  # Harga penutupan
    model = np.polyfit(X.flatten(), y, 1)  # Linear regression
    prediction = np.polyval(model, len(data))  # Prediksi harga berikutnya
    return prediction

# Fungsi untuk menghitung target harga berdasarkan pergerakan
def calculate_target(data, movement_direction):
    last_price = data['close'].iloc[-1]
    
    # Jika pergerakan naik atau turun
    if movement_direction == "NAIK":
        target_price = last_price + (last_price * 0.01)  # Prediksi naik 1%
    elif movement_direction == "TURUN":
        target_price = last_price - (last_price * 0.01)  # Prediksi turun 1%
    
    return target_price

# Fungsi utama untuk bot analisis pergerakan harga
def main():
    symbol = 'BTC/USDT'  # Aset yang akan dianalisis, contoh BTC/USDT
    
    # Ambil data historis
    data = get_historical_data(symbol, timeframe='1h', limit=200)

    # Hitung indikator teknikal
    data_with_indicators = calculate_indicators(data)

    # Prediksi pergerakan harga
    movement_direction, predicted_price = predict_price_movement(data_with_indicators)

    # Hitung target harga berdasarkan pergerakan
    target_price = calculate_target(data_with_indicators, movement_direction)

    # Tampilkan hasil prediksi dan target harga
    print(f"Prediksi Pergerakan: {movement_direction}")
    print(f"Prediksi Harga Berikutnya: {predicted_price}")
    print(f"Target Harga {movement_direction}: {target_price}")

# Jalankan bot
main()