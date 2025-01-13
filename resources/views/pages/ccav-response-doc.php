<?php
// Load the JSON data
$json = file_get_contents(url('json/ccav-response.json'));
$data = json_decode($json, true);

function display_parameter($name, $info)
{
    ?>
<div
    class="bg-white rounded-lg shadow-lg p-6 mb-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
    <div class="border-l-4 border-indigo-500 pl-4">
        <h3 class="text-2xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($name); ?></h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Description</h4>
                <p class="text-gray-700 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($info['description'])); ?>
                </p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Type</h4>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                    <?php echo htmlspecialchars($info['type']); ?>
                </span>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Length</h4>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <?php echo isset($info['length']) ? htmlspecialchars($info['length']) : 'N/A'; ?>
                </span>
            </div>

            <?php if (isset($info['allowed_chars'])): ?>
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Allowed Characters</h4>
                <p class="text-gray-700 font-mono bg-gray-100 p-2 rounded">
                    <?php echo htmlspecialchars($info['allowed_chars']); ?>
                </p>
            </div>
            <?php endif; ?>
            <?php if (isset($info['format'])): ?>
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Format</h4>
                <p class="text-gray-700 font-mono bg-gray-100 p-2 rounded">
                    <?php echo htmlspecialchars($info['format']); ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if (isset($info['allowed_values'])): ?>
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Allowed Values</h4>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($info['allowed_values'] as $value): ?>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <?php echo htmlspecialchars($value); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
}

$pageTitle = "CCAvenue Response Parameters | Quantum IT Innovation";
include VIEW_PATH . 'layouts/layout.php';
?>

<div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-2xl font-extrabold text-gray-900 sm:text-5xl sm:tracking-tight lg:text-4xl">
                CCAvenue Response Parameters
            </h1>
            <p class="mt-4 max-w-3xl mx-auto text-md text-gray-500">
                Complete documentation of all CCAvenue integration parameters and their specifications.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-6 px-4 sm:px-6">
            <?php
            // Iterate over the JSON data and display each parameter
            foreach ($data as $name => $info) {
                display_parameter($name, $info);
            }
            ?>
        </div>
    </div>
</div>

<?php include VIEW_PATH . 'layouts/footer.php'; ?>
