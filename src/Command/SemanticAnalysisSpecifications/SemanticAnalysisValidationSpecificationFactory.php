<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications;

/**
 * Factory for creating semantic analysis validation specifications.
 */
class SemanticAnalysisValidationSpecificationFactory
{
    public function create(): CompositeSemanticAnalysisValidationSpecification
    {
        $composite = new CompositeSemanticAnalysisValidationSpecification();
        
        $composite->add(new PathValidationSpecification());
        $composite->add(new GranularityValidationSpecification());
        $composite->add(new ThresholdValidationSpecification());
        $composite->add(new ViewTypeValidationSpecification());
        
        return $composite;
    }
}
