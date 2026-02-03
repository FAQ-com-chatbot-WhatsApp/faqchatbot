"""Abstract base class for LLM providers.

Defines the interface that all LLM providers must implement.
Follows Interface Segregation Principle from SOLID.
"""

from abc import ABC, abstractmethod


class LLMProvider(ABC):
    """Abstract base class for Large Language Model providers.

    This interface ensures all LLM providers implement a consistent API
    regardless of the underlying service (Gemini, Groq, OpenAI, etc.).
    """

    @abstractmethod
    def generate_response(
        self,
        prompt: str,
        temperature: float | None = None,
        max_tokens: int | None = None,
    ) -> str:
        """Generate a text response from the LLM.

        Args:
            prompt: Input text prompt for the model
            temperature: Sampling temperature (0.0 to 1.0). Lower is more deterministic
            max_tokens: Maximum tokens in response. None uses provider default

        Returns:
            Generated text response from the model

        Raises:
            LLMError: If generation fails or provider is unavailable
        """
        pass

    @abstractmethod
    def is_available(self) -> bool:
        """Check if the provider is currently available.

        Returns:
            True if provider can accept requests, False otherwise
        """
        pass

    @abstractmethod
    def get_provider_name(self) -> str:
        """Get the provider name for logging and identification.

        Returns:
            Provider name (e.g., 'gemini', 'groq')
        """
        pass

    @abstractmethod
    def get_model_name(self) -> str:
        """Get the current model name being used.

        Returns:
            Model identifier (e.g., 'gemini-1.5-flash', 'llama-3.3-70b')
        """
        pass
