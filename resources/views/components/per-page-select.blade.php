@props(['default' => 20])

<select name="per_page" onchange="this.form.submit()" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
    @foreach([10, 25, 50, 100] as $n)
        <option value="{{ $n }}" @selected((int) request('per_page', $default) === $n)>{{ $n }} / page</option>
    @endforeach
</select>
