<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if (!empty($perfil['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars($perfil['foto_perfil']) ?>" 
                                 alt="Foto de perfil" 
                                 class="img-fluid rounded-circle" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                                 style="width: 150px; height: 150px;">
                                <i class="fas fa-user fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($perfil['nombre_tienda'] ?? '') ?></h4>
                    <p class="text-muted mb-3"><?= htmlspecialchars($perfil['email_empresa'] ?? '') ?></p>
                    <a href="<?= url('vendedor/perfil/editar') ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Editar Perfil
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Información de Contacto</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-id-card me-2 text-primary"></i>
                            <strong>RUT/Documento:</strong> <?= htmlspecialchars($perfil['rut_documento'] ?? 'No especificado') ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2 text-primary"></i>
                            <strong>Teléfono:</strong> 
                            <?= !empty($perfil['telefono_contacto']) ? 
                                htmlspecialchars($perfil['telefono_contacto']) : 'No especificado' ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                            <strong>Ubicación:</strong> 
                            <?= !empty($perfil['ciudad_tienda']) || !empty($perfil['pais_tienda']) ? 
                                htmlspecialchars(trim(($perfil['ciudad_tienda'] ?? '') . ', ' . ($perfil['pais_tienda'] ?? ''), ', ')) : 
                                'No especificada' ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-calendar-alt me-2 text-primary"></i>
                            <strong>Miembro desde:</strong> 
                            <?= !empty($perfil['fecha_registro']) ? 
                                date('d/m/Y', strtotime($perfil['fecha_registro'])) : date('d/m/Y') ?>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if (!empty($perfil['redes_sociales'])): 
                $redes = is_string($perfil['redes_sociales']) ? 
                    json_decode($perfil['redes_sociales'], true) : $perfil['redes_sociales'];
                $has_redes = !empty(array_filter($redes));
            ?>
                <?php if ($has_redes): ?>
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Redes Sociales</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-around">
                                <?php if (!empty($redes['facebook'])): ?>
                                    <a href="<?= htmlspecialchars($redes['facebook']) ?>" target="_blank" class="text-primary">
                                        <i class="fab fa-facebook-f fa-2x"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($redes['instagram'])): ?>
                                    <a href="<?= htmlspecialchars($redes['instagram']) ?>" target="_blank" class="text-danger">
                                        <i class="fab fa-instagram fa-2x"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($redes['twitter'])): ?>
                                    <a href="<?= htmlspecialchars($redes['twitter']) ?>" target="_blank" class="text-info">
                                        <i class="fab fa-twitter fa-2x"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($redes['tiktok'])): ?>
                                    <a href="<?= htmlspecialchars($redes['tiktok']) ?>" target="_blank" class="text-dark">
                                        <i class="fab fa-tiktok fa-2x"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Resumen de la Tienda</h5>
                    <a href="<?= url('vendedor/perfil/editar') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($perfil['descripcion_tienda'])): ?>
                        <p class="card-text"><?= nl2br(htmlspecialchars($perfil['descripcion_tienda'])) ?></p>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-store-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Aún no has agregado una descripción para tu tienda.</p>
                            <a href="<?= url('vendedor/perfil/editar') ?>" class="btn btn-link">Agregar descripción</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-box-open fa-2x text-primary"></i>
                            </div>
                            <h3 class="mb-1"><?= $estadisticas['total_productos'] ?? 0 ?></h3>
                            <p class="text-muted mb-0">Productos Publicados</p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 text-center">
                            <a href="<?= url('vendedor/productos') ?>" class="btn btn-sm btn-link text-decoration-none">
                                Ver productos <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-shopping-cart fa-2x text-success"></i>
                            </div>
                            <h3 class="mb-1"><?= $estadisticas['total_pedidos'] ?? 0 ?></h3>
                            <p class="text-muted mb-0">Pedidos Totales</p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 text-center">
                            <a href="<?= url('vendedor/pedidos') ?>" class="btn btn-sm btn-link text-decoration-none">
                                Ver pedidos <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                            </div>
                            <h3 class="mb-1"><?= $estadisticas['productos_bajo_stock'] ?? 0 ?></h3>
                            <p class="text-muted mb-0">Productos con Bajo Stock</p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 text-center">
                            <a href="<?= url('vendedor/productos?estado_stock=bajo_stock') ?>" class="btn btn-sm btn-link text-decoration-none">
                                Ver productos <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-times-circle fa-2x text-danger"></i>
                            </div>
                            <h3 class="mb-1"><?= $estadisticas['productos_agotados'] ?? 0 ?></h3>
                            <p class="text-muted mb-0">Productos Agotados</p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 text-center">
                            <a href="<?= url('vendedor/productos?estado_stock=agotado') ?>" class="btn btn-sm btn-link text-decoration-none">
                                Reponer stock <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($estadisticas['ultimos_pedidos'])): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Últimos Pedidos</h5>
                        <a href="<?= url('vendedor/pedidos') ?>" class="btn btn-sm btn-outline-primary">
                            Ver todos
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pedido #</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas['ultimos_pedidos'] as $pedido): ?>
                                        <tr>
                                            <td>#<?= $pedido['id'] ?></td>
                                            <td><?= htmlspecialchars($pedido['nombre_cliente']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($pedido['fecha_creacion'])) ?></td>
                                            <td>$<?= number_format($pedido['total'], 0, ',', '.') ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadgeClass($pedido['estado']) ?>">
                                                    <?= ucfirst($pedido['estado']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= url('vendedor/pedidos/ver/' . $pedido['id']) ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
