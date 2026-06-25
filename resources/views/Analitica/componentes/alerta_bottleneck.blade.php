@if($bottleneck && $bottleneck['minutos'] > 0)
<div class="bottleneck-card animate-in">
    <div class="bottleneck-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
    </div>
    <div class="bottleneck-content">
        <span class="bottleneck-title">Cuello de Botella Detectado</span>
        <p class="bottleneck-text">
            La etapa <strong>"{{ $bottleneck['etapa'] }}"</strong> presenta una demora crítica de <strong>{{ $bottleneck['label'] }}</strong>.
            <a href="#" class="bottleneck-action">Ver detalles &rarr;</a>
        </p>
    </div>
</div>
@endif
