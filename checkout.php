<?php include 'includes/header.php'; ?>

<div class="container">
    <h1 style="text-align: center; color: white; margin-bottom: 2rem;">Checkout</h1>
    
    <div class="checkout-form">
        <form method="POST" action="process-order.php">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="payment_method">Metode Pembayaran</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="transfer">Transfer Bank</option>
                    <option value="ewallet">E-Wallet</option>
                    <option value="credit_card">Kartu Kredit</option>
                </select>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">Buat Pesanan</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>