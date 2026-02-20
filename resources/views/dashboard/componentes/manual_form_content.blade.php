{{-- Fila 1 --}}
<div class="col-md-4">
    <label class="form-label fw-bold">NÚMERO DE CONTRATO *</label>
    <input type="text" name="NUMERO DE CONTRATO" class="form-control" required>
</div>
<div class="col-md-4">
    <label class="form-label">CONTRATISTA</label>
    <input type="text" name="CONTRATISTA" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">CÉDULA / NIT</label>
    <input type="text" name="CEDULA" class="form-control">
</div>

{{-- Fila 2 --}}
<div class="col-md-4">
    <label class="form-label">RP</label>
    <input type="text" name="RP" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">FECHA RP</label>
    <input type="date" name="FECHA RP" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">VALOR RP</label>
    <input type="number" step="0.01" name="VALOR RP" class="form-control">
</div>

{{-- Fila 3 --}}
<div class="col-md-4">
    <label class="form-label">FECHA DE INICIO</label>
    <input type="date" name="FECHA DE INICIO" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">FECHA DE TERMINACIÓN</label>
    <input type="date" name="FECHA DE TERMINACIÓN" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">SUPERVISOR</label>
    <input type="text" name="SUPERVISOR" class="form-control">
</div>

{{-- Fila 4 --}}
<div class="col-md-4">
    <label class="form-label">N° CUENTA PROCESO</label>
    <input type="number" name="NUMERO DE CUENTA EN PROCESO DE CUENTAS" class="form-control" min="1"
        value="1">
</div>
<div class="col-md-4">
    <label class="form-label">N° PAGOS TOTALES</label>
    <input type="number" name="NUMERO DE PAGOS TOTALES" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">N° FACTURAS RADICADAS</label>
    <input type="number" name="N° DE FACTURAS RADICADA HACIENDA" class="form-control">
</div>

{{-- Fila 5 --}}
<div class="col-md-4">
    <label class="form-label">PORCENTAJE CUENTAS</label>
    <input type="number" step="0.01" name="PORCENTAJE DE CUENTAS" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">ENTIDAD SALUD</label>
    <input type="text" name="ENTIDAD SALUD" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">ENTIDAD PENSIÓN</label>
    <input type="text" name="ENTIDAD PENSIÓN" class="form-control">
</div>

{{-- Fila 6 --}}
<div class="col-md-4">
    <label class="form-label">ENTIDAD ARL</label>
    <input type="text" name="ENTIDAD ARL" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">ULTIMA PLANILLA SS</label>
    <input type="text" name="PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA" class="form-control">
</div>
<div class="col-md-4">
    <label class="form-label">RADICADO POR</label>
    <input type="text" name="RADICADO POR" class="form-control">
</div>

{{-- Fila 7 --}}
<div class="col-md-6">
    <label class="form-label">FECHA RADICACIÓN (INICIAL/CORREC)</label>
    <input type="date" name="FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES" class="form-control">
</div>
<div class="col-md-6">
    <label class="form-label">OBSERVACIONES</label>
    <textarea name="OBSERVACIONES" class="form-control" rows="1"></textarea>
</div>

<hr>
<h6 class="text-primary mt-0">Campos Adicionales (Revisiones / SAP / Hacienda)</h6>

{{-- Fila 8 --}}
<div class="col-md-3">
    <label class="form-label small">ESTADO REVISIÓN 1</label>
    <select name="ESTADO TRAS PRIMERA REVISIÓN" class="form-select form-select-sm">
        <option value="">Seleccione estado...</option>
        @foreach ($todosLosEstados['REV1'] ?? [] as $estado)
            <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label small">FECHA DEVUELTA/SAP</label>
    <input type="date" name="FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP" class="form-control form-control-sm">
</div>
<div class="col-md-3">
    <label class="form-label small">ENVIADA SAP</label>
    <select name="ENVIADA A INGRESO MERCANCIA SAP" class="form-select form-select-sm">
        <option value="">Seleccione estado...</option>
        @foreach ($todosLosEstados['SAP'] ?? [] as $estado)
            <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label small">RESPONSABLE REV</label>
    <input type="text" name="RESPONSABLE_REV" class="form-control form-control-sm">
</div>

{{-- Fila 9 --}}
<div class="col-md-3">
    <label class="form-label small">FECHA FACTURACION</label>
    <input type="date" name="FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES"
        class="form-control form-control-sm">
</div>
<div class="col-md-3">
    <label class="form-label small">EN FACTURACIÓN</label>
    <select name="EN FACTURACIÓN" class="form-select form-select-sm">
        <option value="">Seleccione estado...</option>
        @foreach ($todosLosEstados['FAC'] ?? [] as $estado)
            <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label small">RESPONSABLE FAC</label>
    <input type="text" name="RESPONSABLE_FAC" class="form-control form-control-sm">
</div>
<div class="col-md-3">
    <label class="form-label small">FECHA GEN. FACT.</label>
    <input type="date" name="FECHA EN QUE SE GENERA FACURACIÓN" class="form-control form-control-sm">
</div>

{{-- Fila 10 --}}
<div class="col-md-3">
    <label class="form-label small">FIRMA SECRETARIO</label>
    <select name="FIRMA SECRETARIO" class="form-select form-select-sm">
        <option value="">Seleccione estado...</option>
        @foreach ($todosLosEstados['FIR'] ?? [] as $estado)
            <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label small">FECHA PARA FIRMA</label>
    <input type="date" name="FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO"
        class="form-control form-control-sm">
</div>
<div class="col-md-3">
    <label class="form-label small">ESTADO HACIENDA</label>
    <select name="RADICADA EN HACIENDA" class="form-select form-select-sm">
        <option value="">Seleccione estado...</option>
        @foreach ($todosLosEstados['HAC'] ?? [] as $estado)
            <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label small">FECHA RAD HACIENDA</label>
    <input type="date" name="FECHA DE RADICACIÓN" class="form-control form-control-sm">
</div>

{{-- Fila 11 --}}
<div class="col-md-4">
    <label class="form-label small">ULTIMA FACTURA HACIENDA</label>
    <input type="text" name="ULTIMA FACTURA RADICADA HACIENDA" class="form-control form-control-sm">
</div>
<div class="col-md-4">
    <label class="form-label small">OBS. DEVOL. HACIENDA</label>
    <input type="text" name="OBSERVACIÓN DEVOLUCIÓN HACIENDA" class="form-control form-control-sm">
</div>
<div class="col-md-4">
    <label class="form-label small">DIFERENCIA CUENTAS</label>
    <input type="number" name="DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS"
        class="form-control form-control-sm">
</div>
