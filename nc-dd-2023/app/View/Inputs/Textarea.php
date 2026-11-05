<?php declare(strict_types=1);

namespace App\View\Inputs;

use Illuminate\View\Component;

class Textarea extends Component
{

    /**
     * @var null
     */
    public $name = null;

    /**
     * @var null
     */
    public $value = null;

    /**
     * @var null
     */
    public $label = null;

    /**
     * @var string
     */
    public $class = '';

    /**
     * @var null
     */
    public $helpBlock = null;

    /**
     * @var null
     */
    public $placeholder = null;

    /**
     * Create a new component instance.
     *
     * @param string $name
     * @param null $value
     * @param string $label
     * @param string $class
     * @param null $helpBlock
     * @param null $placeholder
     */
    public function __construct($name = 'name', $value = null, $label = 'Label', $class = '', $helpBlock = null, $placeholder = null)
    {
        $this->name        = $name;
        $this->value       = $value;
        $this->label       = $label;
        $this->class       = $class;
        $this->helpBlock   = $helpBlock;
        $this->placeholder = $placeholder;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.textarea', [
            'name' => $this->name,
            'value' => $this->value,
            'label' => $this->label,
            'class' => $this->class,
            'helpBlock' => $this->helpBlock,
            'placeholder' => $this->placeholder,
        ]);
    }
}
