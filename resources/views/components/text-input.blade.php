@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-lg py-2.5 px-3.5 text-sm outline-none transition-colors']) }}>
