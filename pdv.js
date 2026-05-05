// ============================================================
// PDVo - JavaScript da Página de Caixa/PDV (pdv.js)
// Versão Atualizada: Carregamento Automático de Configurações + PIX + Cores
// ============================================================

let cart    = [];
let caixaId = null;
let currentUser = null;
let scannerActive = false;
let html5QrCode = null;
let sysConfig = {}; // Objeto global para guardar as configs do banco (PIX, Nome, etc)

// --- Inicializar PDV ---
async function initPDV() {
  currentUser = await checkAuth();
  if (!currentUser) return;

  // 1. Busca configurações do banco ANTES de qualquer coisa para evitar refresh
  await loadSystemSettings();
  
  // 2. Carrega estado do caixa
  await loadCaixa();
  
  // 3. Setup de interface
  setupSearch();
  setupPaymentModal();
  renderCart();
}

// --- Buscar configurações do PHP (Garante que dados como PIX estejam prontos) ---
async function loadSystemSettings() {
  try {
    const res = await API.get('/configuracoes.php');
    if (res?.ok) {
      sysConfig = res.data;
      
      // Atualiza o nome da empresa na interface se houver o elemento
      const nomeEmpresaEl = document.getElementById('empresa-nome-display');
      if (nomeEmpresaEl && sysConfig.pix_nome) {
        nomeEmpresaEl.textContent = sysConfig.pix_nome;
      }
      console.log("Configurações do sistema carregadas.");
    }
  } catch (err) {
    console.error("Erro ao carregar configurações:", err);
  }
}

// --- Verificar/abrir caixa ---
async function loadCaixa() {
  const res = await API.get('/caixa.php');
  if (!res) return;
  
  if (res.data && res.data.caixa) {
    caixaId = res.data.caixa.id;
    updateCaixaUI(res.data.caixa);
    document.getElementById('pdv-content')?.classList.remove('opacity-50', 'pointer-events-none');
  } else {
    caixaId = null;
    const infoEl = document.getElementById('caixa-info');
    if (infoEl) infoEl.innerHTML = '<span class="text-red-500 font-bold">CAIXA FECHADO</span>';
    
    document.getElementById('pdv-content')?.classList.add('opacity-50', 'pointer-events-none');
    
    const btnA = document.getElementById('btn-abrir-caixa');
    if (btnA) btnA.classList.remove('hidden');
    
    const btnF = document.getElementById('btn-fechar-caixa');
    if (btnF) btnF.classList.add('hidden');
  }
}

function updateCaixaUI(caixa) {
  const el = document.getElementById('caixa-info');
  if (el) {
    el.textContent = `Caixa #${caixa.id} | Fundo: ${formatCurrency(caixa.fundo_caixa)}`;
  }
  const btn = document.getElementById('btn-abrir-caixa');
  if (btn) btn.classList.add('hidden');
  const btnF = document.getElementById('btn-fechar-caixa');
  if (btnF) btnF.classList.remove('hidden');
}

function showAbrirCaixaModal() {
  const m = document.getElementById('modal-caixa');
  if (m) {
    document.getElementById('saldo-abertura').value = "0";
    m.classList.remove('hidden');
  }
}

async function abrirCaixa() {
  const saldoInput = document.getElementById('saldo-abertura');
  const saldo = parseFloat(saldoInput.value || '0');
  const res = await API.post('/caixa.php', { acao: 'abrir', saldo_abertura: saldo });
  
  if (!res || !res.ok) { 
    toast(res?.data?.error || 'Erro ao abrir caixa.', 'error'); 
    return; 
  }
  
  caixaId = res.data.caixa_id;
  toast('Caixa aberto com sucesso!', 'success');
  document.getElementById('modal-caixa')?.classList.add('hidden');
  await loadCaixa();
}

async function fecharCaixa() {
  if (!caixaId) { toast('Nenhum caixa aberto.', 'error'); return; }
  const saldoStr = prompt('Informe o saldo em dinheiro no fechamento:');
  if (saldoStr === null) return; 
  
  const saldo = parseFloat(saldoStr || '0');
  const res = await API.post('/caixa.php', { acao: 'fechar', caixa_id: caixaId, saldo_fechamento: saldo });
  
  if (!res || !res.ok) { 
    toast(res?.data?.error || 'Erro ao fechar caixa.', 'error'); 
    return; 
  }
  
  toast('Caixa fechado!', 'success');
  caixaId = null;
  setTimeout(() => window.location.reload(), 1200);
}

// --- Busca de produto (Cores Claras e Scanner) ---
function setupSearch() {
  const input = document.getElementById('search-input');
  if (!input) return;
  let timer;
  
  input.addEventListener('input', () => {
    const q = input.value.trim();
    if (/^\d{8,14}$/.test(q)) return;

    clearTimeout(timer);
    timer = setTimeout(async () => {
      if (!q) { 
        document.getElementById('search-results').classList.add('hidden'); 
        return; 
      }
      await searchProduct(q);
    }, 400);
  });

  input.addEventListener('keydown', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const q = input.value.trim();
      if (q) {
        await searchProduct(q, true);
        input.value = '';
      }
    }
  });
}

async function searchProduct(query, exact = false) {
  if (/^\d{5,14}$/.test(query)) {
    const res = await API.get(`/produtos.php?barcode=${encodeURIComponent(query)}`);
    if (res?.ok && res.data.product) {
      addToCart(res.data.product);
      document.getElementById('search-results').classList.add('hidden');
      document.getElementById('search-input').value = '';
      return;
    }
  }

  const res = await API.get(`/produtos.php?busca=${encodeURIComponent(query)}&limit=8`);
  if (!res?.ok) return;
  
  const results = res.data.products || [];
  const container = document.getElementById('search-results');

  if (!results.length) {
    if (exact) toast("Produto não encontrado", "warning");
    container.innerHTML = '<p class="text-slate-600 text-sm p-3 text-center">Nenhum produto encontrado.</p>';
    container.classList.remove('hidden');
    return;
  }

  if (exact && results.length === 1) {
    addToCart(results[0]);
    container.classList.add('hidden');
    return;
  }

  container.innerHTML = results.map(p => `
    <div class="flex items-center gap-3 p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100"
         onclick='addToCart(${JSON.stringify(p).replace(/'/g, "&apos;")}); document.getElementById("search-results").classList.add("hidden"); document.getElementById("search-input").value="";'>
      <img src="${p.imagem_url || 'assets/img/no-image.jpeg'}" class="w-10 h-10 object-cover rounded border border-gray-200 bg-white">
      <div class="flex-1 min-w-0">
        <div class="font-bold text-sm text-slate-900 truncate">${p.nome}</div>
        <div class="text-[10px] text-slate-500 font-medium">
            ${p.codigo_barras || 'SEM REF'} | EST: ${formatNum(p.estoque)}
        </div>
      </div>
      <div class="text-blue-600 font-bold text-sm">
        ${formatCurrency(parseFloat(p.preco || 0))}
      </div>
    </div>
  `).join('');
  container.classList.remove('hidden');
}

// --- Carrinho ---
function addToCart(product) {
  if (!product) return;
  const preco = parseFloat(product.preco || 0);
  const estoqueDisponivel = parseFloat(product.estoque || 0);
  const idx = cart.findIndex(i => i.product_id === product.id);

  if (idx >= 0) {
    if (cart[idx].quantidade + 1 > estoqueDisponivel) {
      toast(`Estoque insuficiente`, 'warning');
      return;
    }
    cart[idx].quantidade += 1;
  } else {
    if (estoqueDisponivel <= 0) {
      toast(`Produto sem estoque!`, 'error');
      return;
    }
    cart.push({
      product_id: product.id,
      nome: product.nome,
      preco_unitario: preco,
      quantidade: 1,
      estoque: estoqueDisponivel,
      unidade: product.unidade || 'UN'
    });
  }
  renderCart();
  toast(`${product.nome} adicionado`, 'success', 800);
}

function renderCart() {
  const container = document.getElementById('cart-items');
  const emptyMsg  = document.getElementById('cart-empty');
  const subtotalEl = document.getElementById('subtotal');
  const totalEl    = document.getElementById('total-value');

  if (!container) return;

  if (!cart.length) {
    container.innerHTML = '';
    emptyMsg?.classList.remove('hidden');
    if (subtotalEl) subtotalEl.textContent = formatCurrency(0);
    if (totalEl)    totalEl.textContent    = formatCurrency(0);
    return;
  }

  emptyMsg?.classList.add('hidden');
  const subtotal = cart.reduce((s, i) => s + (i.preco_unitario * i.quantidade), 0);
  const desconto = parseFloat(document.getElementById('desconto')?.value || '0');
  const total    = Math.max(0, subtotal - desconto);

  container.innerHTML = cart.map((item, i) => `
    <div class="pdv-cart-item bg-slate-50 p-2 rounded-lg mb-2 flex items-center justify-between border border-slate-200 shadow-sm">
      <div class="flex-1 min-w-0 pr-2">
        <div class="text-sm font-bold text-slate-800 truncate">${item.nome}</div>
        <div class="text-[10px] text-slate-500 font-medium">
            ${formatCurrency(item.preco_unitario)} x ${formatNum(item.quantidade)}
        </div>
      </div>
      <div class="flex items-center gap-3">
        <input type="number" class="bg-white border border-slate-300 text-slate-900 w-14 text-center text-xs py-1 rounded outline-none"
               value="${item.quantidade}" onchange="updateQty(${i}, this.value)">
        <span class="text-sm font-bold text-blue-700 min-w-[70px] text-right">
            ${formatCurrency(item.preco_unitario * item.quantidade)}
        </span>
        <button onclick="removeFromCart(${i})" class="text-red-400 hover:text-red-600 p-1">
           <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
        </button>
      </div>
    </div>
  `).join('');

  if (subtotalEl) subtotalEl.textContent = formatCurrency(subtotal);
  if (totalEl)    totalEl.textContent    = formatCurrency(total);
}

function updateQty(idx, val) {
  const qty = parseFloat(val);
  if (isNaN(qty) || qty <= 0) { removeFromCart(idx); return; }
  if (qty > cart[idx].estoque) {
    toast(`Estoque máximo atingido`, 'warning');
    cart[idx].quantidade = cart[idx].estoque;
  } else {
    cart[idx].quantidade = qty;
  }
  renderCart();
}

function removeFromCart(idx) {
  cart.splice(idx, 1);
  renderCart();
}

function getTotal() {
  const subtotal = cart.reduce((s, i) => s + (i.preco_unitario * i.quantidade), 0);
  const desconto = parseFloat(document.getElementById('desconto')?.value || '0');
  return Math.max(0, subtotal - desconto);
}

// --- INTEGRAÇÃO PIX (Sincronizada com sysConfig) ---
async function gerarPixQR() {
  if (!cart.length) { toast('Carrinho vazio.', 'warning'); return; }
  
  // Verifica configurações carregadas do banco sem necessidade de refresh
  if (!sysConfig.pix_chave) {
    toast('Chave PIX não configurada no sistema.', 'error');
    return;
  }

  const total = getTotal();
  const res = await API.post('/pix.php', { 
    valor: total, 
    descricao: "Venda PDVo" 
  });

  if (!res || !res.ok) {
    toast(res?.data?.error || 'Erro ao gerar PIX.', 'error');
    return;
  }

  const payload = res.data.payload;
  document.getElementById('pix-payload').value = payload;
  
  const qrContainer = document.getElementById('qr-container');
  qrContainer.innerHTML = ""; 
  
  new QRCode(qrContainer, {
    text: payload,
    width: 200,
    height: 200,
    colorDark : "#000000",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
  });

  document.getElementById('modal-pix').classList.remove('hidden');
}

function copyPixPayload() {
  const input = document.getElementById('pix-payload');
  input.select();
  navigator.clipboard.writeText(input.value);
  toast('Código PIX copiado!', 'success');
}

// --- Modal de Pagamento e Finalização ---
function setupPaymentModal() {
  const formaPag = document.getElementById('forma-pagamento');
  formaPag?.addEventListener('change', () => {
    const isDinheiro = formaPag.value === 'dinheiro';
    document.getElementById('troco-container')?.classList.toggle('hidden', !isDinheiro);
  });
  document.getElementById('valor-pago')?.addEventListener('input', () => {
    const total = getTotal();
    const pago = parseFloat(document.getElementById('valor-pago').value || 0);
    document.getElementById('troco').textContent = formatCurrency(Math.max(0, pago - total));
  });
}

function openPaymentModal() {
  if (!cart.length) { toast('Carrinho vazio.', 'warning'); return; }
  if (!caixaId) { toast('Abra o caixa primeiro.', 'error'); return; }
  const total = getTotal();
  document.getElementById('pay-total').textContent = formatCurrency(total);
  document.getElementById('valor-pago').value = total.toFixed(2);
  document.getElementById('modal-pagamento').classList.remove('hidden');
}

function closePaymentModal() {
  document.getElementById('modal-pagamento').classList.add('hidden');
}

async function finalizarVenda() {
  const total = getTotal();
  const pago = parseFloat(document.getElementById('valor-pago').value || 0);
  if (pago < total) { toast('Valor insuficiente.', 'warning'); return; }

  const payload = {
    caixa_id: caixaId,
    itens: cart.map(i => ({ product_id: i.product_id, quantidade: i.quantidade, preco_unitario: i.preco_unitario })),
    desconto: parseFloat(document.getElementById('desconto').value || 0),
    forma_pagamento: document.getElementById('forma-pagamento').value,
    valor_pago: pago
  };

  const res = await API.post('/vendas.php', payload);
  if (res?.ok) {
    toast('Venda finalizada!', 'success');
    cart = [];
    renderCart();
    closePaymentModal();
    document.getElementById('desconto').value = 0;
    document.getElementById('search-input').value = '';
  } else {
    toast(res?.data?.error || 'Erro na venda.', 'error');
  }
}

// --- Scanner de Código de Barras ---
function toggleScanner() {
  if (scannerActive) stopScanner(); else startScanner();
}

function startScanner() {
  document.getElementById('scanner-container').classList.remove('hidden');
  scannerActive = true;
  html5QrCode = new Html5Qrcode('scanner-reader');
  html5QrCode.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, (text) => {
    stopScanner();
    searchProduct(text, true);
  }).catch(() => { toast('Erro na câmera.', 'error'); stopScanner(); });
}

function stopScanner() {
  if (html5QrCode && scannerActive) html5QrCode.stop();
  document.getElementById('scanner-container').classList.add('hidden');
  scannerActive = false;
}

document.addEventListener('DOMContentLoaded', initPDV);
