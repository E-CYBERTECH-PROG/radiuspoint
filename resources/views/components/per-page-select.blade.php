@props(['default' => 20])

<select name="per_page" onchange="this.form.submit()" class="form-select form-select-sm w-auto d-inline-block">
    @foreach([10, 25, 50, 100] as $n)
        <option value="{{ $n }}" @selected((int) request('per_page', $default) === $n)>{{ $n }}</option>
    @endforeach
</select>
