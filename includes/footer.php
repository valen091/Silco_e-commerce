    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Silco</h5>
                    <p>Tu tienda en línea de confianza para encontrar los mejores productos al mejor precio.</p>
                </div>
                <div class="col-md-4">
                    <h5>Categorías</h5>
                    <ul class="list-unstyled">
                        <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                            <li><a href="/Silco/categoria.php?id=<?= $category['id'] ?>" class="text-white-50"><?= htmlspecialchars($category['nombre']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contacto</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-envelope"></i> contacto@silco.com</li>
                        <li><i class="bi bi-telephone"></i> +1 234 567 890</li>
                        <li><i class="bi bi-geo-alt"></i> Ciudad, País</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Silco. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
