// DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    const foodCalories = window.foodCalories || {};
    const t = window.translations || { kcal: 'kcal', total: 'Total', selectFood: 'Select Food', gMl: 'g/ml', removeItem: 'Remove food item' };

    function updateCaloriePreview(item) {
        const select = item.querySelector('.food-select');
        const quantityInput = item.querySelector('.quantity-input');
        const preview = item.querySelector('.calorie-preview');
        const food = select.value;
        const quantity = parseFloat(quantityInput.value) || 0;
        const calories = food ? Math.round((foodCalories[food] || 0) * quantity / 100) : 0;

        preview.textContent = `${calories} ${t.kcal}`;
        preview.dataset.calories = calories;

        const meal = item.closest('.mt-meal');
        const totalPreview = meal.querySelector('.total-calories');
        const items = meal.querySelectorAll('.meal-item');
        const totalCalories = Array.from(items).reduce((sum, i) => sum + parseInt(i.querySelector('.calorie-preview').dataset.calories || 0), 0);
        totalPreview.textContent = `${t.total}: ${totalCalories} ${t.kcal}`;
        totalPreview.dataset.totalCalories = totalCalories;
    }

    function attachMealItemListeners(item) {
        const select = item.querySelector('.food-select');
        const input = item.querySelector('.quantity-input');
        const removeBtn = item.querySelector('.remove-food-btn');

        select.addEventListener('change', () => {
            input.style.display = select.value ? 'block' : 'none';
            removeBtn.style.display = item.parentElement.children.length > 1 ? 'flex' : 'none';
            updateCaloriePreview(item);
        });

        input.addEventListener('input', () => updateCaloriePreview(item));

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                const container = item.parentElement;
                item.remove();
                if (container.querySelector('.meal-item')) updateCaloriePreview(container.querySelector('.meal-item'));
            });
        }
    }

    document.querySelectorAll('.meal-item').forEach(attachMealItemListeners);

    // Add Food Item
    document.querySelectorAll('.mt-add-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const meal = btn.dataset.meal;
            const container = btn.closest('.mt-meal').querySelector('.mt-meal__items');
            const count = container.querySelectorAll('.meal-item').length;
            const div = document.createElement('div');
            div.classList.add('mt-meal__item', 'meal-item');

            div.innerHTML = `
                <select class="mt-select food-select" name="meals[${meal}][${count}][food]" aria-label="${t.selectFood}">
                    ${window.foodOptionsHTML || ''}
                </select>
                <input type="number" class="mt-input quantity-input" name="meals[${meal}][${count}][quantity]" placeholder="${t.gMl}" style="display:none;" min="0" step="1">
                <div class="mt-cal-preview calorie-preview" data-calories="0">0 ${t.kcal}</div>
                <button type="button" class="mt-remove-btn remove-food-btn" style="display:flex;" aria-label="${t.removeItem}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>`;

            container.appendChild(div);
            attachMealItemListeners(div);
        });
    });

    // Form Submission
    const mealForm = document.getElementById('meal-form');
    const calculateBtn = document.getElementById('calculate-btn');

    mealForm.addEventListener('submit', async e => {
        e.preventDefault();
        document.querySelectorAll('.error-message').forEach(el => el.remove());

        const formData = new FormData(mealForm);
        const mealsData = {};
        let valid = true;

        formData.forEach((value, key) => {
            const matches = key.match(/meals\[(\w+)\]\[(\d+)\]\[(\w+)\]/);
            if (matches) {
                const [_, meal, index, field] = matches;
                if (!mealsData[meal]) mealsData[meal] = [];
                if (!mealsData[meal][index]) mealsData[meal][index] = {};
                mealsData[meal][index][field] = value;
            }
        });

        Object.keys(mealsData).forEach(meal => {
            mealsData[meal].forEach((item, index) => {
                if (item.food && (!item.quantity || parseFloat(item.quantity) <= 0)) {
                    valid = false;
                    const mealEl = document.querySelector(`.mt-meal__items[data-meal="${meal}"]`);
                    if (mealEl) {
                        const mealItem = mealEl.querySelectorAll('.meal-item')[index];
                        if (mealItem) {
                            const error = document.createElement('div');
                            error.className = 'error-message';
                            error.textContent = 'Quantity must be a positive number';
                            mealItem.appendChild(error);
                        }
                    }
                }
            });
        });

        if (!valid) {
            showNotification('Please correct the errors in the form', 'error');
            return;
        }

        // Optimistic UI Update
        const historyTbody = document.querySelector('.history-table tbody');
        if (historyTbody) {
            const noDataRow = historyTbody.querySelector('.no-data');
            if (noDataRow) noDataRow.remove();

            Object.keys(mealsData).forEach(meal => {
                mealsData[meal].forEach(item => {
                    if (item.food && item.quantity) {
                        const calories = Math.round((foodCalories[item.food] || 0) * parseFloat(item.quantity) / 100);
                        const row = document.createElement('tr');
                        row.classList.add('optimistic');
                        row.innerHTML = `
                            <td>${new Date().toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                            <td>${meal}</td>
                            <td>${item.food}</td>
                            <td>${item.quantity} ${t.gMl}</td>
                            <td>${calories} ${t.kcal}</td>`;
                        historyTbody.insertBefore(row, historyTbody.firstChild);
                    }
                });
            });
        }

        calculateBtn.disabled = true;
        const origHTML = calculateBtn.innerHTML;
        calculateBtn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg> ...`;

        try {
            const response = await axios.post(mealForm.action, formData, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (response.data.success) {
                showNotification(response.data.message || 'Meals logged successfully!', 'success');
                const historySection = document.getElementById('history-section');
                if (response.data.historyHtml && historySection) {
                    historySection.innerHTML = response.data.historyHtml;
                }
                mealForm.reset();
                document.querySelectorAll('.quantity-input').forEach(input => input.style.display = 'none');
                document.querySelectorAll('.remove-food-btn').forEach(btn => btn.style.display = 'none');
                document.querySelectorAll('.calorie-preview').forEach(preview => {
                    preview.textContent = `0 ${t.kcal}`;
                    preview.dataset.calories = '0';
                });
                document.querySelectorAll('.total-calories').forEach(total => {
                    total.textContent = `${t.total}: 0 ${t.kcal}`;
                    total.dataset.totalCalories = '0';
                });
                attachPaginationListeners();
            } else {
                showNotification(response.data.message || 'Error logging meals', 'error');
                document.querySelectorAll('.optimistic').forEach(row => row.remove());
            }
        } catch (error) {
            showNotification(error.response?.data?.message || 'Network error, please try again', 'error');
            document.querySelectorAll('.optimistic').forEach(row => row.remove());
        } finally {
            calculateBtn.disabled = false;
            calculateBtn.innerHTML = origHTML;
        }
    });

    // Notification Display
    function showNotification(message, type) {
        const notification = document.getElementById('notification');
        if (!notification) return;
        notification.textContent = message;
        notification.className = `mt-notification ${type}`;
        setTimeout(() => { notification.className = 'mt-notification'; }, 4000);
    }

    // AJAX Pagination
    function attachPaginationListeners() {
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', async e => {
                e.preventDefault();
                if (link.classList.contains('disabled')) return;

                const url = link.getAttribute('href');
                const historySection = document.getElementById('history-section');
                const scrollY = window.scrollY;

                try {
                    const response = await axios.get(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(response.data, 'text/html');
                    const newSection = doc.getElementById('history-section');
                    if (newSection && historySection) {
                        historySection.innerHTML = newSection.innerHTML;
                        window.scrollTo(0, scrollY);
                        attachPaginationListeners();
                    }
                } catch (error) {
                    showNotification('Error loading page', 'error');
                }
            });
        });
    }

    attachPaginationListeners();
});
