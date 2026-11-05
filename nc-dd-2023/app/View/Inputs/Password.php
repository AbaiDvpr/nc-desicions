<?php declare(strict_types=1);

namespace App\View\Inputs;

use Illuminate\View\Component;

class Password extends Component
{

    /**
     * @var null
     */
    public $name = null;

    /**
     * @var null
     */
    public $label = null;

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
     * @param string $label
     * @param null $helpBlock
     * @param null $placeholder
     */
    public function __construct($name = 'name', $label = 'Label', $helpBlock = null, $placeholder = null)
    {
        $this->name        = $name;
        $this->label       = $label;
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
        return view('components.inputs.password', [
            'name'        => $this->name,
            'label'       => $this->label,
            'helpBlock'   => $this->helpBlock,
            'placeholder' => $this->placeholder,
        ]);
    }
}
