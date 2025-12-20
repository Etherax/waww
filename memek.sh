#!/bin/bash

# Harus dijalankan sebagai root
if [[ $EUID -ne 0 ]]; then
   echo "Script ini harus dijalankan sebagai root!"
   exit 1
fi

username="Kitings"
password="Memek@123123"

# Cek apakah user sudah ada
if id "$username" &>/dev/null; then
    echo "User '$username' sudah ada."
    exit 0
fi

# Buat user dan home dir
useradd -m -s /bin/bash "$username"

# Set password
echo "$username:$password" | chpasswd

# Tambahkan ke grup sudo
usermod -aG sudo "$username"

echo "✅ User '$username' berhasil dibuat dengan password '$password'"
echo "🔑 User ini juga ditambahkan ke grup sudo."
