<?php 
session_start();
include __DIR__ . '/includes/header.php'; 
?>

        <!-- Conteúdo da página -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-tachometer-alt text-primary"></i>
                    Bem-vindo ao Sistema
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-3"></i>
                                <h5>150</h5>
                                <p class="text-muted mb-0">Clientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-box fa-2x text-success mb-3"></i>
                                <h5>89</h5>
                                <p class="text-muted mb-0">Produtos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-shopping-cart fa-2x text-warning mb-3"></i>
                                <h5>23</h5>
                                <p class="text-muted mb-0">Vendas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-2x text-danger mb-3"></i>
                                <h5>R$ 45.600</h5>
                                <p class="text-muted mb-0">Faturamento</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-success mb-4">
                    <i class="fas fa-check-circle"></i>
                    <strong>Sistema Atualizado!</strong> Novo layout responsivo funcionando perfeitamente.
                </div>
                
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Novo Registro
                    </button>
                    <button class="btn btn-outline-primary">
                        <i class="fas fa-download"></i>
                        Exportar Dados
                    </button>
                    <button class="btn btn-success">
                        <i class="fas fa-chart-bar"></i>
                        Ver Relatórios
                    </button>
                </div>
            </div>
        </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
