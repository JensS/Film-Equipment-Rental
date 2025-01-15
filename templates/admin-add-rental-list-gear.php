<div class="rental-item">
    <div class="column" style="width:60%">
    <label for="equipment[]">Gear:</label>
    <select name="equipment[]" class="equipment-select" required>
        <option value="">Select Equipment</option>
        <?php foreach (fer_get_categories() as $slug => $category): ?>
            <optgroup label="<?php echo esc_attr($category["name"]); ?>">
                <?php 
                $category_items = array_filter($items, function($equip) use ($slug) {
                    return $equip->category === $slug;
                });
                foreach ($category_items as $equip): ?>
                    <option value="<?php echo $equip->id; ?>" 
                            data-rate="<?php echo $equip->daily_rate; ?>"
                            <?php if (isset($item) && $equip->id == $item->equipment_id) echo 'selected'; ?>>
                        <?php echo esc_html((isset($equip->brand) ? $equip->brand . ' ' : '') . $equip->name); ?> 
                        (<?php echo fer_format_currency($equip->daily_rate); ?>/day)
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>
                </div>
                <div class="column">
    <label for="earnings[]">Rented rate:</label>
    <input type="number" name="earnings[]" step="0.01" min="0" 
           value="<?php echo isset($item) ? esc_attr($item->earnings) : ''; ?>"
           placeholder="0.00"
           required>
                </div>
                <div class="column" style="width:10%;text-align:right">
    <button type="button" class="button remove-item">×</button>
                </div>
</div>
