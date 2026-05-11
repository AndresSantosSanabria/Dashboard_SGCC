@if($bottleneck && $bottleneck['minutos'] > 0)
<div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 py-3 px-4 mb-4 animate-in">
    <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
    <div>
        <div class="fw-bold text-danger uppercase small">Cuello de Botella Detectado</div>
        <div class="text-secondary small">La etapa <strong>"{{ $bottleneck['etapa'] }}"</strong> presenta una demora crítica de {{ $bottleneck['label'] }}.</div>
    </div>
</div>
@endif
