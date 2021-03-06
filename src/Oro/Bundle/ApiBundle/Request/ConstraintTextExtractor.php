<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\ApiBundle\Validator\Constraints\ConstraintWithStatusCodeInterface;
use Oro\Bundle\SecurityBundle\Validator\Constraints\FieldAccessGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator;

/**
 * The default implementation of extractor that retrieves information from a validation constraint object.
 */
class ConstraintTextExtractor implements ConstraintTextExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConstraintStatusCode(Validator\Constraint $constraint)
    {
        if ($constraint instanceof ConstraintWithStatusCodeInterface) {
            return $constraint->getStatusCode();
        }
        if ($constraint instanceof FieldAccessGranted) {
            return Response::HTTP_FORBIDDEN;
        }

        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraintCode(Validator\Constraint $constraint)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraintType(Validator\Constraint $constraint)
    {
        return ValueNormalizerUtil::humanizeClassName(\get_class($constraint), 'Constraint');
    }
}
