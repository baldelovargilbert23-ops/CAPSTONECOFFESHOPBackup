import 'preline';

const init = () => window.HSStaticMethods?.autoInit();

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();

document.addEventListener('livewire:navigated', init);